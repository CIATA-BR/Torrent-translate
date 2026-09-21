<?php

namespace App\Console\Commands;

use App\Services\TranslationCatalogSynchronizer;
use Illuminate\Console\Command;

class SyncTranslationCatalog extends Command
{
    protected $signature = 'translations:sync-pot {path? : Caminho do arquivo POT}';
    protected $description = 'Sincroniza o catálogo canônico inglês sem apagar traduções existentes.';

    public function handle(TranslationCatalogSynchronizer $synchronizer): int
    {
        $path = $this->argument('path') ?: resource_path('translation_catalog/serrebitorrent.pot');

        try {
            $stats = $synchronizer->syncFile((string) $path);
        } catch (\Throwable $e) {
            $this->error($e->getMessage());
            return self::FAILURE;
        }

        $this->info($stats['total'].' termos encontrados no POT.');
        $this->line('Novos: '.$stats['added']);
        $this->line('Reativados: '.$stats['reactivated']);
        $this->line('Mantidos: '.$stats['unchanged']);
        $this->line('Desativados: '.$stats['deactivated']);

        return self::SUCCESS;
    }
}
