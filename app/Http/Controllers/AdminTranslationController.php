<?php

namespace App\Http\Controllers;

use App\Models\Locale;
use App\Models\TranslationSource;
use App\Services\GitHubTranslationRepositoryService;
use App\Services\TranslationIntegrityValidator;
use App\Services\TranslationPoService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;

class AdminTranslationController extends Controller
{
    public function __construct(
        protected TranslationIntegrityValidator $validator,
        protected TranslationPoService $po,
        protected GitHubTranslationRepositoryService $github
    ) {}

    public function index()
    {
        $sources = TranslationSource::query()
            ->where('active', true)
            ->with('translations')
            ->get();

        $total = $sources->count();

        $locales = Locale::query()
            ->where('active', true)
            ->orderBy('name')
            ->get()
            ->map(function (Locale $locale) use ($sources, $total) {
                $translated = 0;
                $invalid = 0;

                foreach ($sources as $source) {
                    $translation = $source->translations->firstWhere('locale_id', $locale->id);
                    $text = trim((string) $translation?->text);

                    if ($text === '') {
                        continue;
                    }

                    $translated++;

                    if ($this->validator->validate($source->msgid, $text) !== []) {
                        $invalid++;
                    }
                }

                $percent = $total > 0 ? (int) floor(($translated / $total) * 100) : 0;

                return [
                    'locale' => $locale,
                    'translated' => $translated,
                    'total' => $total,
                    'percent' => $percent,
                    'invalid' => $invalid,
                    'publishable' => $total > 0 && $translated === $total && $invalid === 0,
                ];
            });

        return view('admin.translations', [
            'locales' => $locales,
            'githubConfigured' => filled(config('torrent.github.token')),
            'sourceRef' => config('torrent.github.source_ref'),
            'publishBase' => config('torrent.github.publish_base'),
        ]);
    }

    public function sync(Request $request)
    {
        abort_unless(filled(config('torrent.github.token')), 422, __('portal.admin_github_not_configured'));

        try {
            $pot = $this->github->fetchPot();
            $path = resource_path('translation_catalog/serrebitorrent.pot');

            if (! is_dir(dirname($path))) {
                mkdir(dirname($path), 0775, true);
            }

            file_put_contents($path, $pot);

            $exitCode = Artisan::call('translations:sync-pot', ['path' => $path]);

            if ($exitCode !== 0) {
                throw new \RuntimeException(trim(Artisan::output()) ?: 'Falha ao sincronizar o catálogo.');
            }
        } catch (\Throwable $e) {
            Log::error('Falha ao sincronizar catálogo pelo painel administrativo.', [
                'exception_class' => $e::class,
                'message' => $e->getMessage(),
            ]);

            return back()->withErrors([
                'github' => __('portal.admin_sync_failed'),
            ]);
        }

        return back()->with('status', __('portal.admin_sync_success'));
    }

    public function publish(Request $request)
    {
        abort_unless(filled(config('torrent.github.token')), 422, __('portal.admin_github_not_configured'));

        $data = $request->validate([
            'locale_id' => ['required', 'exists:locales,id'],
        ]);

        $locale = Locale::query()->findOrFail((int) $data['locale_id']);

        try {
            $contents = $this->po->build($locale);
            $url = $this->github->publishPo($locale->code, $contents);
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

        return back()
            ->with('status', __('portal.admin_publish_success', ['locale' => $locale->code]))
            ->with('published_pr_url', $url);
    }
}
