<?php

namespace App\Console\Commands;

use App\Models\Country;
use App\Models\Language;
use App\Models\Locale;
use App\Models\Translation;
use App\Models\TranslationSource;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ImportTranslationCatalog extends Command
{
    protected $signature = 'translations:import-catalog';
    protected $description = 'Importa o catálogo inglês do SerrebiTorrent e as traduções pt-BR já existentes.';

    public function handle(): int
    {
        $potPath = resource_path('translation_catalog/serrebitorrent.pot');
        $ptPath = resource_path('translation_catalog/pt-BR.json');

        if (! is_file($potPath) || ! is_file($ptPath)) {
            $this->error('Arquivos de catálogo não encontrados.');
            return self::FAILURE;
        }

        $english = $this->parsePot((string) file_get_contents($potPath));
        $portuguese = json_decode((string) file_get_contents($ptPath), true, flags: JSON_THROW_ON_ERROR);

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
                        ['text' => $translated, 'status' => 'approved', 'updated_by' => null]
                    );
                }
            }
        });

        $this->info(count($english).' termos em inglês importados.');
        $this->info(count($portuguese).' traduções pt-BR existentes importadas.');

        return self::SUCCESS;
    }

    private function parsePot(string $contents): array
    {
        $entries = [];
        $blocks = preg_split('/\R{2,}/', trim($contents)) ?: [];

        foreach ($blocks as $block) {
            if (! preg_match('/^msgid\s+"((?:\\.|[^"\\])*)"/m', $block, $match)) {
                continue;
            }

            $value = stripcslashes($match[1]);

            if ($value !== '') {
                $entries[] = $value;
            }
        }

        return array_values(array_unique($entries));
    }
}
