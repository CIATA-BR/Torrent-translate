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
        $sources = TranslationSource::query()
            ->where('active', true)
            ->with(['translations' => fn ($q) => $q->where('locale_id', $locale->id)])
            ->orderBy('id')
            ->get();

        $missing = [];
        $invalid = [];

        foreach ($sources as $source) {
            $translation = $source->translations->first();
            $text = trim((string) $translation?->text);

            if ($text === '') {
                $missing[] = $source->id;
                continue;
            }

            if ($this->validator->validate($source->msgid, $text) !== []) {
                $invalid[] = $source->id;
            }
        }

        if ($missing !== [] || $invalid !== []) {
            throw new RuntimeException(
                'Catálogo incompleto ou inválido. Sem tradução: '.count($missing)
                .'. Inválidas: '.count($invalid).'.'
            );
        }

        $output = '';
        $output .= 'msgid ""'."\n";
        $output .= 'msgstr ""'."\n";
        $output .= '"Project-Id-Version: SerrebiTorrent\\n"'."\n";
        $output .= '"Language: '.$locale->code.'\\n"'."\n";
        $output .= '"MIME-Version: 1.0\\n"'."\n";
        $output .= '"Content-Type: text/plain; charset=UTF-8\\n"'."\n";
        $output .= '"Content-Transfer-Encoding: 8bit\\n"'."\n\n";

        foreach ($sources as $source) {
            $translation = $source->translations->first();

            $output .= 'msgid "'.$this->escape((string) $source->msgid).'"'."\n";
            $output .= 'msgstr "'.$this->escape((string) $translation->text).'"'."\n\n";
        }

        return $output;
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
