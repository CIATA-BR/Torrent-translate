<?php

namespace App\Services;

use App\Models\TranslationSource;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class TranslationCatalogSynchronizer
{
    public function __construct(
        protected GettextCatalogService $catalog
    ) {}

    public function syncContents(string $contents): array
    {
        $entries = $this->catalog->parsePot($contents);

        if ($entries === []) {
            throw new RuntimeException('Nenhum msgid válido foi encontrado no catálogo.');
        }

        return $this->syncEntries($entries);
    }

    public function syncFile(string $path): array
    {
        $entries = $this->catalog->parsePotFile($path);

        if ($entries === []) {
            throw new RuntimeException('Nenhum msgid válido foi encontrado no catálogo.');
        }

        return $this->syncEntries($entries);
    }

    private function syncEntries(array $entries): array
    {
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

        return [
            'total' => count($entries),
            ...$stats,
        ];
    }
}
