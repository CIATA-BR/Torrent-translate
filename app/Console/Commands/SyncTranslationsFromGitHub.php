<?php

namespace App\Console\Commands;

use App\Services\GitHubTranslationRepositoryService;
use App\Services\TranslationCatalogSynchronizer;
use Illuminate\Console\Command;

class SyncTranslationsFromGitHub extends Command
{
    protected $signature = 'translations:sync-github {--ref= : Branch/tag/commit de origem}';
    protected $description = 'Baixa o POT do SerrebiTorrent no GitHub e sincroniza o banco sem escrever no checkout da aplicação.';

    public function handle(
        GitHubTranslationRepositoryService $github,
        TranslationCatalogSynchronizer $synchronizer
    ): int {
        try {
            $pot = $github->fetchPot($this->option('ref') ?: null);
            $stats = $synchronizer->syncContents($pot);
        } catch (\Throwable $e) {
            $this->error($e->getMessage());
            return self::FAILURE;
        }

        $this->info($stats['total'].' termos encontrados no POT remoto.');
        $this->line('Novos: '.$stats['added']);
        $this->line('Reativados: '.$stats['reactivated']);
        $this->line('Mantidos: '.$stats['unchanged']);
        $this->line('Desativados: '.$stats['deactivated']);

        return self::SUCCESS;
    }
}
