<?php

namespace App\Services;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class GitHubTranslationRepositoryService
{
    private function client(): PendingRequest
    {
        $token = trim((string) config('torrent.github.token'));

        if ($token === '') {
            throw new RuntimeException('TORRENT_GITHUB_TOKEN não configurado.');
        }

        return Http::withToken($token)
            ->acceptJson()
            ->withHeaders([
                'X-GitHub-Api-Version' => '2022-11-28',
                'User-Agent' => 'CIATA-Torrent-Translate',
            ]);
    }

    public function fetchPot(?string $ref = null): string
    {
        $repository = (string) config('torrent.github.repository');
        $path = (string) config('torrent.github.pot_path');
        $ref = $ref ?: (string) config('torrent.github.source_ref');

        $response = $this->client()->get(
            "https://api.github.com/repos/{$repository}/contents/{$path}",
            ['ref' => $ref]
        );

        if (! $response->successful()) {
            throw new RuntimeException(
                'Falha ao obter POT no GitHub: '.$response->status().' - '.$response->body()
            );
        }

        return $this->decodeGitHubContent($response->json('content'));
    }

    public function publishTranslation(
        string $localeCode,
        string $localeName,
        string $poContents,
        string $webCatalogContents,
        ?string $base = null
    ): string {
        $repository = (string) config('torrent.github.repository');
        $base = $base ?: (string) config('torrent.github.publish_base');

        $refResponse = $this->client()->get(
            "https://api.github.com/repos/{$repository}/git/ref/heads/".rawurlencode($base)
        );

        if (! $refResponse->successful()) {
            throw new RuntimeException(
                'Falha ao localizar branch base no GitHub: '.$refResponse->status().' - '.$refResponse->body()
            );
        }

        $baseSha = (string) $refResponse->json('object.sha');
        $branch = 'translations/'.strtolower($localeCode).'-'.now()->format('Ymd-His');

        $createRef = $this->client()->post(
            "https://api.github.com/repos/{$repository}/git/refs",
            ['ref' => 'refs/heads/'.$branch, 'sha' => $baseSha]
        );

        if (! $createRef->successful()) {
            throw new RuntimeException(
                'Falha ao criar branch de tradução: '.$createRef->status().' - '.$createRef->body()
            );
        }

        $localesPath = rtrim((string) config('torrent.github.locales_path'), '/');
        $webLocalesPath = rtrim((string) config('torrent.github.web_locales_path'), '/');

        $this->writeFile(
            $repository,
            $branch,
            $localesPath.'/'.$localeCode.'.po',
            $poContents,
            'i18n: update '.$localeCode.' translation'
        );

        $this->writeFile(
            $repository,
            $branch,
            $webLocalesPath.'/'.$localeCode.'.json',
            $webCatalogContents,
            'i18n: compile '.$localeCode.' web catalog'
        );

        $indexPath = $webLocalesPath.'/index.json';
        $index = $this->readJsonFile($repository, $branch, $indexPath);
        $languages = [];

        foreach (($index['languages'] ?? []) as $item) {
            if (is_array($item) && filled($item['code'] ?? null) && filled($item['name'] ?? null)) {
                $languages[(string) $item['code']] = (string) $item['name'];
            }
        }

        $languages[$localeCode] = $localeName;
        uksort($languages, 'strnatcasecmp');

        $indexContents = json_encode([
            'languages' => array_map(
                fn (string $code, string $name) => ['code' => $code, 'name' => $name],
                array_keys($languages),
                array_values($languages)
            ),
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR)."\n";

        $this->writeFile(
            $repository,
            $branch,
            $indexPath,
            $indexContents,
            'i18n: update web language index'
        );

        $pr = $this->client()->post(
            "https://api.github.com/repos/{$repository}/pulls",
            [
                'title' => 'i18n: atualização '.$localeCode,
                'head' => $branch,
                'base' => $base,
                'body' => "Atualização {$localeCode} gerada pelo Torrent Translate da CIATA.\n\n"
                    ."Inclui o catálogo PO usado no desktop (Windows, macOS e Linux), "
                    ."o JSON compilado para a Web UI e o índice de idiomas.\n\n"
                    ."O merge permanece manual.",
            ]
        );

        if (! $pr->successful()) {
            throw new RuntimeException(
                'Arquivos publicados, mas falhou ao abrir PR: '.$pr->status().' - '.$pr->body()
            );
        }

        return (string) $pr->json('html_url');
    }

    private function writeFile(
        string $repository,
        string $branch,
        string $path,
        string $contents,
        string $message
    ): void {
        $existing = $this->client()->get(
            "https://api.github.com/repos/{$repository}/contents/{$path}",
            ['ref' => $branch]
        );

        $payload = [
            'message' => $message,
            'content' => base64_encode($contents),
            'branch' => $branch,
        ];

        if ($existing->successful() && filled($existing->json('sha'))) {
            $payload['sha'] = (string) $existing->json('sha');
        }

        $write = $this->client()->put(
            "https://api.github.com/repos/{$repository}/contents/{$path}",
            $payload
        );

        if (! $write->successful()) {
            throw new RuntimeException(
                'Falha ao gravar '.$path.' no GitHub: '.$write->status().' - '.$write->body()
            );
        }
    }

    private function readJsonFile(string $repository, string $ref, string $path): array
    {
        $response = $this->client()->get(
            "https://api.github.com/repos/{$repository}/contents/{$path}",
            ['ref' => $ref]
        );

        if ($response->status() === 404) {
            return [];
        }

        if (! $response->successful()) {
            throw new RuntimeException(
                'Falha ao ler '.$path.' no GitHub: '.$response->status().' - '.$response->body()
            );
        }

        $decoded = json_decode($this->decodeGitHubContent($response->json('content')), true);

        return is_array($decoded) ? $decoded : [];
    }

    private function decodeGitHubContent(mixed $content): string
    {
        $normalized = str_replace("\n", '', (string) $content);
        $decoded = base64_decode($normalized, true);

        if ($decoded === false) {
            throw new RuntimeException('Conteúdo retornado pelo GitHub é inválido.');
        }

        return $decoded;
    }
}
