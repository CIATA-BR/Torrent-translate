<?php

namespace App\Services;

use RuntimeException;

class GettextCatalogService
{
    public function parsePotFile(string $path): array
    {
        if (! is_file($path) || ! is_readable($path)) {
            throw new RuntimeException('Arquivo POT não encontrado ou sem permissão de leitura: '.$path);
        }

        return $this->parsePot((string) file_get_contents($path));
    }

    public function parsePot(string $contents): array
    {
        $entries = [];
        $current = null;
        $collecting = false;

        foreach (preg_split('/\R/', $contents) ?: [] as $line) {
            $trimmed = trim($line);

            if (str_starts_with($trimmed, 'msgid ')) {
                if ($collecting && $current !== null && $current !== '') {
                    $entries[] = $current;
                }

                $current = $this->decodeQuoted(substr($trimmed, 6));
                $collecting = true;
                continue;
            }

            if ($collecting && str_starts_with($trimmed, '"')) {
                $current .= $this->decodeQuoted($trimmed);
                continue;
            }

            if ($collecting && $trimmed === '') {
                if ($current !== null && $current !== '') {
                    $entries[] = $current;
                }

                $current = null;
                $collecting = false;
            }
        }

        if ($collecting && $current !== null && $current !== '') {
            $entries[] = $current;
        }

        return array_values(array_unique($entries));
    }

    private function decodeQuoted(string $value): string
    {
        $value = trim($value);

        if (strlen($value) < 2 || $value[0] !== '"' || $value[strlen($value) - 1] !== '"') {
            return '';
        }

        return stripcslashes(substr($value, 1, -1));
    }
}
