<?php

namespace App\Http\Controllers;

use App\Models\Locale;
use App\Models\Translation;
use App\Models\TranslationRevision;
use App\Models\TranslationSource;
use App\Services\AuditTrail;
use App\Services\TranslationIntegrityValidator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\StreamedResponse;

class TranslationController extends Controller
{
    public function __construct(
        protected TranslationIntegrityValidator $validator,
        protected AuditTrail $audit
    ) {}

    public function index(Request $request)
    {
        $user = $request->user();
        abort_unless($user?->locale_id, 403);

        $sourceLanguage = $request->string('source_lang')->toString() ?: 'en-US';
        abort_unless(in_array($sourceLanguage, ['en-US', 'pt-BR'], true), 422);

        $targetLocaleId = (int) ($request->input('target_locale') ?: $user->locale_id);
        $targetLocale = Locale::query()->where('active', true)->findOrFail($targetLocaleId);
        $ptBr = Locale::query()->where('code', 'pt-BR')->first();

        $filter = $request->string('filter')->toString();
        $search = trim($request->string('q')->toString());
        $editId = (int) $request->input('edit');

        $available = TranslationSource::query()->where('active', true);

        if ($sourceLanguage === 'pt-BR') {
            abort_unless($ptBr, 503, 'O catálogo pt-BR ainda não foi importado.');
            $available->whereHas('translations', fn ($q) => $q
                ->where('locale_id', $ptBr->id)
                ->where('status', 'approved')
                ->whereNotNull('text')
                ->where('text', '<>', ''));
        }

        if ($search !== '') {
            if ($sourceLanguage === 'en-US') {
                $available->where('msgid', 'like', '%'.$search.'%');
            } else {
                $available->whereHas('translations', fn ($q) => $q
                    ->where('locale_id', $ptBr->id)
                    ->where('text', 'like', '%'.$search.'%'));
            }
        }

        if ($filter === 'untranslated') {
            $available->whereDoesntHave('translations', fn ($q) => $q
                ->where('locale_id', $targetLocale->id)
                ->whereNotNull('text')
                ->where('text', '<>', ''));
        }

        $query = clone $available;
        $query->with(['translations' => fn ($q) => $q->whereIn(
            'locale_id',
            array_values(array_unique(array_filter([$targetLocale->id, $ptBr?->id])))
        )]);

        $metrics = $this->catalogMetrics($targetLocale);

        return view('translations.index', [
            'sources' => $query->paginate(25)->withQueryString(),
            'locales' => Locale::query()->where('active', true)->orderBy('name')->get(),
            'targetLocale' => $targetLocale,
            'sourceLanguage' => $sourceLanguage,
            'ptBr' => $ptBr,
            'filter' => $filter,
            'search' => $search,
            'editId' => $editId,
            ...$metrics,
        ]);
    }

    public function store(Request $request, TranslationSource $source)
    {
        $user = $request->user();
        abort_unless($user?->locale_id, 403);

        $data = $request->validate([
            'text' => ['required', 'string'],
            'target_locale' => ['required', 'exists:locales,id'],
            'source_lang' => ['required', 'in:en-US,pt-BR'],
            'filter' => ['nullable', 'in:untranslated'],
            'q' => ['nullable', 'string', 'max:255'],
            'page' => ['nullable', 'integer', 'min:1'],
        ]);

        $text = trim($data['text']);
        $issues = $this->validator->validate($source->msgid, $text);

        if ($issues !== []) {
            $messages = array_map(
                fn (array $issue) => __($issue['key'], [
                    'source' => $issue['source'] === '' ? __('portal.validation_none') : $issue['source'],
                    'translation' => $issue['translation'] === '' ? __('portal.validation_none') : $issue['translation'],
                ]),
                $issues
            );

            throw ValidationException::withMessages([
                'text' => $messages,
            ]);
        }

        $translation = Translation::query()->firstOrNew([
            'translation_source_id' => $source->id,
            'locale_id' => (int) $data['target_locale'],
        ]);

        $oldText = $translation->text;
        $oldStatus = $translation->status;

        $translation->fill([
            'updated_by' => $user->id,
            'text' => $text,
            'status' => 'pending_review',
        ])->save();

        TranslationRevision::query()->create([
            'translation_id' => $translation->id,
            'user_id' => $user->id,
            'old_text' => $oldText,
            'new_text' => $translation->text,
            'old_status' => $oldStatus,
            'new_status' => $translation->status,
        ]);

        $this->audit->record(
            'translation.submitted',
            $user,
            Translation::class,
            $translation->id,
            [
                'source_id' => $source->id,
                'locale_id' => (int) $data['target_locale'],
                'old_status' => $oldStatus,
                'new_status' => $translation->status,
            ]
        );

        $targetLocale = Locale::query()->findOrFail((int) $data['target_locale']);
        $metrics = $this->catalogMetrics($targetLocale);

        $query = [
            'source_lang' => $data['source_lang'],
            'target_locale' => $data['target_locale'],
        ];

        if (($data['filter'] ?? null) === 'untranslated') {
            $query['filter'] = 'untranslated';
        }

        if (filled($data['q'] ?? null)) {
            $query['q'] = trim((string) $data['q']);
        }

        if (! empty($data['page']) && (int) $data['page'] > 1) {
            $query['page'] = (int) $data['page'];
        }

        return redirect()
            ->route('translations.index', $query)
            ->with('status', __('portal.progress_with_validation', [
                'translated' => $metrics['translated'],
                'total' => $metrics['total'],
                'percent' => $metrics['percent'],
                'invalid' => $metrics['invalid'],
            ]));
    }

    public function export(Request $request): StreamedResponse
    {
        $data = $request->validate([
            'target_locale' => ['required', 'exists:locales,id'],
            'source_lang' => ['required', 'in:en-US,pt-BR'],
        ]);

        $locale = Locale::query()->findOrFail((int) $data['target_locale']);

        $sources = TranslationSource::query()
            ->where('active', true)
            ->with(['translations' => fn ($q) => $q
                ->where('locale_id', $locale->id)
                ->where('status', 'approved')])
            ->orderBy('id')
            ->get();

        $missing = [];
        $invalid = [];

        foreach ($sources as $source) {
            $translation = $source->translations->first();
            $text = trim((string) $translation?->text);

            if ($text === '') {
                $missing[] = $source->id;
                continue;
            }

            if ($this->validator->validate($source->msgid, $text) !== []) {
                $invalid[] = $source->id;
            }
        }

        if ($missing !== [] || $invalid !== []) {
            Log::warning('Exportação de tradução bloqueada por catálogo incompleto ou inválido.', [
                'locale' => $locale->code,
                'missing_count' => count($missing),
                'invalid_count' => count($invalid),
                'missing_source_ids' => array_slice($missing, 0, 20),
                'invalid_source_ids' => array_slice($invalid, 0, 20),
            ]);

            abort(422, __('portal.export_validation_failed', [
                'missing' => count($missing),
                'invalid' => count($invalid),
            ]));
        }

        $filename = $locale->code.'.po';

        return response()->streamDownload(function () use ($sources, $locale): void {
            echo 'msgid ""'."\n";
            echo 'msgstr ""'."\n";
            echo '"Project-Id-Version: SerrebiTorrent\\n"'."\n";
            echo '"Language: '.$locale->code.'\\n"'."\n";
            echo '"MIME-Version: 1.0\\n"'."\n";
            echo '"Content-Type: text/plain; charset=UTF-8\\n"'."\n";
            echo '"Content-Transfer-Encoding: 8bit\\n"'."\n\n";

            foreach ($sources as $source) {
                $translation = $source->translations->first();
                echo 'msgid "'.$this->poEscape($source->msgid).'"'."\n";
                echo 'msgstr "'.$this->poEscape((string) $translation->text).'"'."\n\n";
            }
        }, $filename, ['Content-Type' => 'text/x-gettext-translation; charset=UTF-8']);
    }

    private function catalogMetrics(Locale $locale): array
    {
        $sources = TranslationSource::query()
            ->where('active', true)
            ->with(['translations' => fn ($q) => $q->where('locale_id', $locale->id)])
            ->get();

        $total = $sources->count();
        $translated = 0;
        $approved = 0;
        $pendingReview = 0;
        $invalid = 0;

        foreach ($sources as $source) {
            $text = trim((string) $source->translations->first()?->text);

            if ($text === '') {
                continue;
            }

            $translated++;

            $translation = $source->translations->first();
            if ($translation?->status === 'approved') {
                $approved++;
            } else {
                $pendingReview++;
            }

            if ($this->validator->validate($source->msgid, $text) !== []) {
                $invalid++;
            }
        }

        return [
            'total' => $total,
            'translated' => $translated,
            'approved' => $approved,
            'pendingReview' => $pendingReview,
            'invalid' => $invalid,
            'percent' => $total > 0 ? (int) floor(($translated / $total) * 100) : 0,
            'publishable' => $total > 0 && $approved === $total && $invalid === 0,
        ];
    }

    private function poEscape(string $value): string
    {
        return str_replace(
            ["\\", "\""],
            ["\\\\", "\\\""],
            str_replace(["\r\n", "\r", "\n"], '\\n', $value)
        );
    }
}
