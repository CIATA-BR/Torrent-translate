<?php

namespace App\Services;

use App\Models\Locale;
use App\Models\TranslationSource;
use RuntimeException;

class TranslationPoService
{
    public function __construct(
        protected TranslationIntegrityValidator $validator
    ) {}

    public function build(Locale $locale): string
    {
        $translations = $this->validatedTranslations($locale);

        $output = '';
        $output .= 'msgid ""'."\n";
        $output .= 'msgstr ""'."\n";
        $output .= '"Project-Id-Version: SerrebiTorrent\\n"'."\n";
        $output .= '"Language: '.$this->escape($locale->code).'\\n"'."\n";
        $output .= '"X-Language-Name: '.$this->escape($locale->name).'\\n"'."\n";
        $output .= '"X-Serrebi-Validation: strict\\n"'."\n";
        $output .= '"MIME-Version: 1.0\\n"'."\n";
        $output .= '"Content-Type: text/plain; charset=UTF-8\\n"'."\n";
        $output .= '"Content-Transfer-Encoding: 8bit\\n"'."\n\n";

        foreach ($translations as $msgid => $text) {
            $output .= 'msgid "'.$this->escape($msgid).'"'."\n";
            $output .= 'msgstr "'.$this->escape($text).'"'."\n\n";
        }

        return $output;
    }

    public function buildWebCatalog(Locale $locale): string
    {
        $translations = $this->validatedTranslations($locale);
        uksort($translations, 'strnatcasecmp');

        return json_encode([
            'language' => $locale->code,
            'name' => $locale->name,
            'translations' => $translations,
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR)."\n";
    }

    private function validatedTranslations(Locale $locale): array
    {
        $sources = TranslationSource::query()
            ->where('active', true)
            ->with(['translations' => fn ($q) => $q
                ->where('locale_id', $locale->id)
                ->where('status', 'approved')])
            ->orderBy('id')
            ->get();

        $missing = [];
        $invalid = [];
        $translations = [];

        foreach ($sources as $source) {
            $translation = $source->translations->first();
            $text = trim((string) $translation?->text);

            if ($text === '') {
                $missing[] = $source->id;
                continue;
            }

            if ($this->validator->validate($source->msgid, $text) !== []) {
                $invalid[] = $source->id;
                continue;
            }

            $translations[(string) $source->msgid] = $text;
        }

        if ($missing !== [] || $invalid !== []) {
            throw new RuntimeException(
                'Catálogo incompleto ou inválido. Sem tradução: '.count($missing)
                .'. Inválidas: '.count($invalid).'.'
            );
        }

        return $translations;
    }

    private function escape(string $value): string
    {
        return str_replace(
            ["\\", "\""],
            ["\\\\", "\\\""],
            str_replace(["\r\n", "\r", "\n"], '\\n', $value)
        );
    }
}
