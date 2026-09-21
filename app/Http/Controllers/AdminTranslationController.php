<?php

namespace App\Http\Controllers;

use App\Models\Locale;
use App\Models\TranslationSource;
use App\Services\GitHubTranslationRepositoryService;
use App\Services\TranslationCatalogSynchronizer;
use App\Services\TranslationIntegrityValidator;
use App\Services\TranslationPoService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class AdminTranslationController extends Controller
{
    public function __construct(
        protected TranslationIntegrityValidator $validator,
        protected TranslationPoService $po,
        protected GitHubTranslationRepositoryService $github,
        protected TranslationCatalogSynchronizer $synchronizer
    ) {}

    public function index()
    {
        $sources = TranslationSource::query()
            ->where('active', true)
            ->with('translations')
            ->get();

        $total = $sources->count();
        $githubConfigured = filled(config('torrent.github.token'));

        $localeModels = Locale::query()
            ->where('active', true)
            ->orderBy('name')
            ->get();

        $openPullRequests = [];
        $githubStatusError = false;

        if ($githubConfigured && $localeModels->isNotEmpty()) {
            try {
                $openPullRequests = $this->github->openTranslationPullRequests(
                    $localeModels->pluck('code')->all()
                );
            } catch (\Throwable $e) {
                $githubStatusError = true;

                Log::warning('Falha ao consultar PRs abertas de tradução.', [
                    'exception_class' => $e::class,
                    'message' => $e->getMessage(),
                ]);
            }
        }

        $locales = $localeModels->map(function (Locale $locale) use ($sources, $total, $openPullRequests) {
            $translated = 0;
            $approved = 0;
            $pendingReview = 0;
            $invalid = 0;

            foreach ($sources as $source) {
                $translation = $source->translations->firstWhere('locale_id', $locale->id);
                $text = trim((string) $translation?->text);

                if ($text === '') {
                    continue;
                }

                $translated++;

                if ($translation?->status === 'approved') {
                    $approved++;
                } else {
                    $pendingReview++;
                }

                if ($this->validator->validate($source->msgid, $text) !== []) {
                    $invalid++;
                }
            }

            $percent = $total > 0 ? (int) floor(($translated / $total) * 100) : 0;

            return [
                'locale' => $locale,
                'translated' => $translated,
                'approved' => $approved,
                'pending_review' => $pendingReview,
                'total' => $total,
                'percent' => $percent,
                'invalid' => $invalid,
                'publishable' => $total > 0 && $approved === $total && $invalid === 0,
                'pull_request' => $openPullRequests[$locale->code] ?? null,
            ];
        });

        return view('admin.translations', [
            'locales' => $locales,
            'githubConfigured' => $githubConfigured,
            'githubStatusError' => $githubStatusError,
            'sourceRef' => config('torrent.github.source_ref'),
            'publishBase' => config('torrent.github.publish_base'),
        ]);
    }

    public function sync(Request $request)
    {
        abort_unless(filled(config('torrent.github.token')), 422, __('portal.admin_github_not_configured'));

        try {
            $pot = $this->github->fetchPot();
            $stats = $this->synchronizer->syncContents($pot);
        } catch (\Throwable $e) {
            Log::error('Falha ao sincronizar catálogo pelo painel administrativo.', [
                'exception_class' => $e::class,
                'message' => $e->getMessage(),
            ]);

            return back()->withErrors([
                'github' => __('portal.admin_sync_failed'),
            ]);
        }

        return back()->with('status', __('portal.admin_sync_success_details', [
            'total' => $stats['total'],
            'added' => $stats['added'],
            'reactivated' => $stats['reactivated'],
            'deactivated' => $stats['deactivated'],
        ]));
    }

    public function publish(Request $request)
    {
        abort_unless(filled(config('torrent.github.token')), 422, __('portal.admin_github_not_configured'));

        $data = $request->validate([
            'locale_id' => ['required', 'exists:locales,id'],
        ]);

        $locale = Locale::query()->findOrFail((int) $data['locale_id']);

        try {
            $poContents = $this->po->build($locale);
            $webCatalogContents = $this->po->buildWebCatalog($locale);
            $result = $this->github->publishTranslation(
                $locale->code,
                $locale->name,
                $poContents,
                $webCatalogContents
            );
        } catch (\Throwable $e) {
            Log::error('Falha ao publicar tradução pelo painel administrativo.', [
                'locale' => $locale->code,
                'exception_class' => $e::class,
                'message' => $e->getMessage(),
            ]);

            return back()->withErrors([
                'github' => __('portal.admin_publish_failed', ['locale' => $locale->code]),
            ]);
        }

        $message = $result['created']
            ? __('portal.admin_publish_success', ['locale' => $locale->code, 'number' => $result['number']])
            : __('portal.admin_update_success', ['locale' => $locale->code, 'number' => $result['number']]);

        return back()
            ->with('status', $message)
            ->with('published_pr_url', $result['url'])
            ->with('published_pr_number', $result['number']);
    }
}
