<?php

namespace App\Console\Commands;

use App\Models\TranslationSource;
use App\Services\GettextCatalogService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class SyncTranslationCatalog extends Command
{
    protected $signature = 'translations:sync-pot {path? : Caminho do arquivo POT}';
    protected $description = 'Sincroniza o catálogo canônico inglês sem apagar traduções existentes.';

    public function handle(GettextCatalogService $catalog): int
    {
        $path = $this->argument('path') ?: resource_path('translation_catalog/serrebitorrent.pot');

        try {
            $entries = $catalog->parsePotFile((string) $path);
        } catch (\Throwable $e) {
            $this->error($e->getMessage());
            return self::FAILURE;
        }

        if ($entries === []) {
            $this->error('Nenhum msgid válido foi encontrado no catálogo.');
            return self::FAILURE;
        }

        $stats = DB::transaction(function () use ($entries): array {
            $seenHashes = [];
            $added = 0;
            $reactivated = 0;
            $unchanged = 0;

            foreach ($entries as $msgid) {
                $hash = hash('sha256', "\0".$msgid);
                $seenHashes[] = $hash;

                $source = TranslationSource::query()->where('source_hash', $hash)->first();

                if (! $source) {
                    TranslationSource::query()->create([
                        'msgid' => $msgid,
                        'context' => null,
                        'source_hash' => $hash,
                        'active' => true,
                    ]);
                    $added++;
                    continue;
                }

                if (! $source->active) {
                    $source->update(['msgid' => $msgid, 'active' => true]);
                    $reactivated++;
                    continue;
                }

                if ($source->msgid !== $msgid) {
                    $source->update(['msgid' => $msgid]);
                }

                $unchanged++;
            }

            $deactivated = TranslationSource::query()
                ->where('active', true)
                ->whereNotIn('source_hash', $seenHashes)
                ->update(['active' => false]);

            return compact('added', 'reactivated', 'unchanged', 'deactivated');
        });

        $this->info(count($entries).' termos encontrados no POT.');
        $this->line('Novos: '.$stats['added']);
        $this->line('Reativados: '.$stats['reactivated']);
        $this->line('Mantidos: '.$stats['unchanged']);
        $this->line('Desativados: '.$stats['deactivated']);

        return self::SUCCESS;
    }
}
