<?php

namespace App\Console\Commands;

use App\Models\Country;
use App\Models\Language;
use App\Models\Locale;
use App\Models\Translation;
use App\Models\TranslationSource;
use App\Services\GettextCatalogService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ImportTranslationCatalog extends Command
{
    protected $signature = 'translations:import-catalog';
    protected $description = 'Importa o catálogo inglês do SerrebiTorrent e as traduções pt-BR já existentes.';

    public function handle(GettextCatalogService $catalog): int
    {
        $potPath = resource_path('translation_catalog/serrebitorrent.pot');
        $ptPath = resource_path('translation_catalog/pt-BR.json');

        if (! is_file($potPath) || ! is_file($ptPath)) {
            $this->error('Arquivos de catálogo não encontrados.');
            return self::FAILURE;
        }

        try {
            $english = $catalog->parsePotFile($potPath);
            $portuguese = json_decode(
                (string) file_get_contents($ptPath),
                true,
                flags: JSON_THROW_ON_ERROR
            );
        } catch (\Throwable $e) {
            $this->error($e->getMessage());
            return self::FAILURE;
        }

        if (! is_array($portuguese)) {
            $this->error('Catálogo pt-BR inválido.');
            return self::FAILURE;
        }

        $br = Country::query()->where('iso2', 'BR')->firstOrFail();
        $us = Country::query()->where('iso2', 'US')->firstOrFail();
        $pt = Language::query()->where('code', 'pt')->firstOrFail();
        $en = Language::query()->where('code', 'en')->firstOrFail();

        $ptBr = Locale::query()->firstOrCreate(
            ['code' => 'pt-BR'],
            ['country_id' => $br->id, 'language_id' => $pt->id, 'name' => 'Português — Brasil', 'active' => true]
        );

        Locale::query()->firstOrCreate(
            ['code' => 'en-US'],
            ['country_id' => $us->id, 'language_id' => $en->id, 'name' => 'English — United States', 'active' => true]
        );

        DB::transaction(function () use ($english, $portuguese, $ptBr): void {
            TranslationSource::query()->update(['active' => false]);

            foreach ($english as $msgid) {
                $source = TranslationSource::query()->updateOrCreate(
                    ['source_hash' => hash('sha256', "\0".$msgid)],
                    ['msgid' => $msgid, 'context' => null, 'active' => true]
                );

                $translated = $portuguese[$msgid] ?? null;

                if (is_string($translated) && trim($translated) !== '') {
                    Translation::query()->updateOrCreate(
                        ['translation_source_id' => $source->id, 'locale_id' => $ptBr->id],
                        ['text' => trim($translated), 'status' => 'approved', 'updated_by' => null]
                    );
                }
            }
        });

        $this->info(count($english).' termos em inglês importados.');
        $this->info(count($portuguese).' entradas pt-BR disponíveis no arquivo de origem.');

        return self::SUCCESS;
    }
}
