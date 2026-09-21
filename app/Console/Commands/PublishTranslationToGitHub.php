<?php

namespace App\Console\Commands;

use App\Models\Locale;
use App\Services\GitHubTranslationRepositoryService;
use App\Services\TranslationPoService;
use Illuminate\Console\Command;

class PublishTranslationToGitHub extends Command
{
    protected $signature = 'translations:publish {locale : Locale, ex.: es-PY} {--base= : Branch base do PR}';
    protected $description = 'Gera o PO concluído, publica em uma branch e abre PR no SerrebiTorrent.';

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
            $contents = $po->build($locale);
            $url = $github->publishPo(
                $locale->code,
                $contents,
                $this->option('base') ?: null
            );
        } catch (\Throwable $e) {
            $this->error($e->getMessage());
            return self::FAILURE;
        }

        $this->info('Tradução publicada e PR aberto.');
        $this->line($url);

        return self::SUCCESS;
    }
}
