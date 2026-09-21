<?php

namespace App\Console\Commands;

use App\Services\GitHubTranslationRepositoryService;
use Illuminate\Console\Command;

class SyncTranslationsFromGitHub extends Command
{
    protected $signature = 'translations:sync-github {--ref= : Branch/tag/commit de origem}';
    protected $description = 'Baixa o POT do SerrebiTorrent no GitHub e sincroniza o banco local.';

    public function handle(GitHubTranslationRepositoryService $github): int
    {
        try {
            $pot = $github->fetchPot($this->option('ref') ?: null);
            $path = resource_path('translation_catalog/serrebitorrent.pot');

            if (! is_dir(dirname($path))) {
                mkdir(dirname($path), 0775, true);
            }

            file_put_contents($path, $pot);

            $this->info('POT atualizado a partir do GitHub.');

            return $this->call('translations:sync-pot', ['path' => $path]);
        } catch (\Throwable $e) {
            $this->error($e->getMessage());
            return self::FAILURE;
        }
    }
}
