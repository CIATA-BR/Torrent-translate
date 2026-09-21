<?php

namespace App\Http\Controllers;

use App\Models\Locale;
use App\Models\Translation;
use App\Models\TranslationRevision;
use App\Models\TranslationSource;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class TranslationController extends Controller
{
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

        $totalQuery = TranslationSource::query()->where('active', true);
        if ($sourceLanguage === 'pt-BR' && $ptBr) {
            $totalQuery->whereHas('translations', fn ($q) => $q
                ->where('locale_id', $ptBr->id)
                ->whereNotNull('text')
                ->where('text', '<>', ''));
        }

        $total = $totalQuery->count();
        $translated = (clone $totalQuery)->whereHas('translations', fn ($q) => $q
            ->where('locale_id', $targetLocale->id)
            ->whereNotNull('text')
            ->where('text', '<>', ''))->count();
        $percent = $total > 0 ? (int) floor(($translated / $total) * 100) : 0;

        return view('translations.index', [
            'sources' => $query->paginate(25)->withQueryString(),
            'locales' => Locale::query()->where('active', true)->orderBy('name')->get(),
            'targetLocale' => $targetLocale,
            'sourceLanguage' => $sourceLanguage,
            'ptBr' => $ptBr,
            'filter' => $filter,
            'search' => $search,
            'editId' => $editId,
            'total' => $total,
            'translated' => $translated,
            'percent' => $percent,
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

        $translation = Translation::query()->firstOrNew([
            'translation_source_id' => $source->id,
            'locale_id' => (int) $data['target_locale'],
        ]);

        $oldText = $translation->text;
        $oldStatus = $translation->status;

        $translation->fill([
            'updated_by' => $user->id,
            'text' => trim($data['text']),
            'status' => 'approved',
        ])->save();

        TranslationRevision::query()->create([
            'translation_id' => $translation->id,
            'user_id' => $user->id,
            'old_text' => $oldText,
            'new_text' => $translation->text,
            'old_status' => $oldStatus,
            'new_status' => $translation->status,
        ]);

        $totalQuery = TranslationSource::query()->where('active', true);

        if ($data['source_lang'] === 'pt-BR') {
            $ptBr = Locale::query()->where('code', 'pt-BR')->firstOrFail();
            $totalQuery->whereHas('translations', fn ($q) => $q
                ->where('locale_id', $ptBr->id)
                ->whereNotNull('text')
                ->where('text', '<>', ''));
        }

        $total = $totalQuery->count();
        $translated = (clone $totalQuery)->whereHas('translations', fn ($q) => $q
            ->where('locale_id', (int) $data['target_locale'])
            ->whereNotNull('text')
            ->where('text', '<>', ''))->count();
        $percent = $total > 0 ? (int) floor(($translated / $total) * 100) : 0;

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
            ->with('status', __('portal.progress', compact('translated', 'total', 'percent')));
    }

    public function export(Request $request): StreamedResponse
    {
        $data = $request->validate([
            'target_locale' => ['required', 'exists:locales,id'],
            'source_lang' => ['required', 'in:en-US,pt-BR'],
        ]);

        $locale = Locale::query()->findOrFail((int) $data['target_locale']);
        $ptBr = Locale::query()->where('code', 'pt-BR')->first();

        $sources = TranslationSource::query()
            ->where('active', true)
            ->when($data['source_lang'] === 'pt-BR', fn ($q) => $q->whereHas('translations', fn ($t) => $t
                ->where('locale_id', $ptBr?->id)
                ->whereNotNull('text')
                ->where('text', '<>', '')))
            ->with(['translations' => fn ($q) => $q->where('locale_id', $locale->id)])
            ->orderBy('id')
            ->get();

        $missing = $sources->filter(fn ($source) => trim((string) optional($source->translations->first())->text) === '');
        abort_if($missing->isNotEmpty(), 422, 'A tradução ainda não está 100% concluída.');

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

    private function poEscape(string $value): string
    {
        return str_replace(
            ["\\", "\""],
            ["\\\\", "\\\""],
            str_replace(["\r\n", "\r", "\n"], '\\n', $value)
        );
    }
}
