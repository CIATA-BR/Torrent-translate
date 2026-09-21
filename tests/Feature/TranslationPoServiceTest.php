<?php

namespace Tests\Feature;

use App\Models\Country;
use App\Models\Language;
use App\Models\Locale;
use App\Models\Translation;
use App\Models\TranslationSource;
use App\Services\TranslationPoService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TranslationPoServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_generated_catalog_opts_into_strict_upstream_validation(): void
    {
        $country = Country::query()->create(['iso2' => 'BR', 'name' => 'Brasil']);
        $language = Language::query()->create(['code' => 'pt', 'name' => 'Português']);
        $locale = Locale::query()->create([
            'country_id' => $country->id,
            'language_id' => $language->id,
            'code' => 'pt-BR',
            'name' => 'Português — Brasil',
            'active' => true,
        ]);
        $source = TranslationSource::query()->create([
            'msgid' => '&Open\tCtrl+O',
            'context' => null,
            'source_hash' => hash('sha256', "\0&Open\tCtrl+O"),
            'active' => true,
        ]);
        Translation::query()->create([
            'translation_source_id' => $source->id,
            'locale_id' => $locale->id,
            'text' => '&Abrir\tCtrl+O',
            'status' => 'approved',
        ]);

        $po = app(TranslationPoService::class)->build($locale);

        $this->assertStringContainsString(
            '"X-Serrebi-Validation: strict\\n"',
            $po
        );
    }
}
