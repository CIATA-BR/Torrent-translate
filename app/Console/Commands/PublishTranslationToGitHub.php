<?php

namespace App\Console\Commands;

use App\Models\Locale;
use App\Services\GitHubTranslationRepositoryService;
use App\Services\TranslationPoService;
use Illuminate\Console\Command;

class PublishTranslationToGitHub extends Command
{
    protected $signature = 'translations:publish {locale : Locale, ex.: es-PY} {--base= : Branch base do PR}';
    protected $description = 'Gera os artefatos concluídos, publica em uma branch e abre ou atualiza PR no SerrebiTorrent.';

    public function handle(
        TranslationPoService $po,
        GitHubTranslationRepositoryService $github
    ): int {
        $localeCode = trim((string) $this->argument('locale'));
        $locale = Locale::query()->where('code', $localeCode)->first();

        if (! $locale) {
            $this->error('Locale não encontrado: '.$localeCode);
            return self::FAILURE;
        }

        try {
            $poContents = $po->build($locale);
            $webCatalogContents = $po->buildWebCatalog($locale);
            $result = $github->publishTranslation(
                $locale->code,
                $locale->name,
                $poContents,
                $webCatalogContents,
                $this->option('base') ?: null
            );
        } catch (\Throwable $e) {
            $this->error($e->getMessage());
            return self::FAILURE;
        }

        $this->info(
            $result['created']
                ? 'Tradução publicada e PR aberto.'
                : 'PR de tradução existente atualizado.'
        );
        $this->line('#'.$result['number'].' '.$result['url']);

        return self::SUCCESS;
    }
}
