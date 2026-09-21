<?php

namespace App\Services;

class TranslationIntegrityValidator
{
    public function validate(string $source, string $translation): array
    {
        $issues = [];

        $sourceNamed = $this->namedPlaceholders($source);
        $translationNamed = $this->namedPlaceholders($translation);

        if ($sourceNamed !== $translationNamed) {
            $issues[] = [
                'key' => 'portal.validation_named_placeholders',
                'source' => implode(', ', $sourceNamed),
                'translation' => implode(', ', $translationNamed),
            ];
        }

        $sourcePrintf = $this->printfPlaceholders($source);
        $translationPrintf = $this->printfPlaceholders($translation);

        if ($sourcePrintf !== $translationPrintf) {
            $issues[] = [
                'key' => 'portal.validation_printf_placeholders',
                'source' => implode(', ', $sourcePrintf),
                'translation' => implode(', ', $translationPrintf),
            ];
        }

        $sourceTabs = $this->tabSuffixes($source);
        $translationTabs = $this->tabSuffixes($translation);

        if ($sourceTabs !== $translationTabs) {
            $issues[] = [
                'key' => 'portal.validation_shortcuts',
                'source' => implode(', ', $sourceTabs),
                'translation' => implode(', ', $translationTabs),
            ];
        }

        $sourceNewlines = substr_count($source, "\n");
        $translationNewlines = substr_count($translation, "\n");

        if ($sourceNewlines !== $translationNewlines) {
            $issues[] = [
                'key' => 'portal.validation_newlines',
                'source' => (string) $sourceNewlines,
                'translation' => (string) $translationNewlines,
            ];
        }

        $sourceAccelerators = $this->acceleratorCount($source);
        $translationAccelerators = $this->acceleratorCount($translation);

        if ($sourceAccelerators !== $translationAccelerators) {
            $issues[] = [
                'key' => 'portal.validation_accelerators',
                'source' => (string) $sourceAccelerators,
                'translation' => (string) $translationAccelerators,
            ];
        }

        return $issues;
    }

    private function namedPlaceholders(string $text): array
    {
        preg_match_all('/\{[A-Za-z_][A-Za-z0-9_]*(?::[^{}]+)?\}/u', $text, $matches);
        $values = $matches[0] ?? [];
        sort($values);

        return $values;
    }

    private function printfPlaceholders(string $text): array
    {
        $withoutEscapedPercent = str_replace('%%', '', $text);
        preg_match_all('/%(?:\d+\$)?[-+0 #]*\d*(?:\.\d+)?[bcdeEfFgGosuxX]/', $withoutEscapedPercent, $matches);
        $values = $matches[0] ?? [];
        sort($values);

        return $values;
    }

    private function tabSuffixes(string $text): array
    {
        preg_match_all('/\t([^\r\n]+)/u', $text, $matches);
        $values = array_map('trim', $matches[1] ?? []);
        sort($values);

        return $values;
    }

    private function acceleratorCount(string $text): int
    {
        preg_match_all('/(?<!&)&(?!&)/', $text, $matches);

        return count($matches[0] ?? []);
    }
}
