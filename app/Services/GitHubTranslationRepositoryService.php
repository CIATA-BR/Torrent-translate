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
        $repository = $this->sourceRepository();
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

    public function openTranslationPullRequests(array $localeCodes, ?string $base = null): array
    {
        $pullRequestRepository = $this->pullRequestRepository();
        $publishRepository = $this->publishRepository();
        $base = $base ?: (string) config('torrent.github.publish_base');

        $response = $this->client()->get(
            "https://api.github.com/repos/{$pullRequestRepository}/pulls",
            [
                'state' => 'open',
                'base' => $base,
                'per_page' => 100,
                'sort' => 'updated',
                'direction' => 'desc',
            ]
        );

        if (! $response->successful()) {
            throw new RuntimeException(
                'Falha ao consultar Pull Requests de tradução: '
                .$response->status().' - '.$response->body()
            );
        }

        $wanted = [];
        foreach ($localeCodes as $code) {
            $wanted[strtolower((string) $code)] = (string) $code;
        }

        $result = [];

        foreach ($response->json() ?? [] as $pr) {
            $branch = (string) data_get($pr, 'head.ref', '');
            $headRepo = (string) data_get($pr, 'head.repo.full_name', '');

            if ($branch === '' || ($headRepo !== '' && strcasecmp($headRepo, $publishRepository) !== 0)) {
                continue;
            }

            foreach ($wanted as $normalized => $original) {
                $stableBranch = 'translations/'.$normalized;
                $timestampPattern = '/^'.preg_quote($stableBranch, '/').'-\\d{8}-\\d{6}$/';

                if ($branch !== $stableBranch && preg_match($timestampPattern, $branch) !== 1) {
                    continue;
                }

                if (isset($result[$original])) {
                    continue;
                }

                $result[$original] = [
                    'number' => (int) data_get($pr, 'number'),
                    'url' => (string) data_get($pr, 'html_url'),
                    'branch' => $branch,
                    'title' => (string) data_get($pr, 'title'),
                    'updated_at' => (string) data_get($pr, 'updated_at'),
                ];
            }
        }

        return $result;
    }

    public function publishTranslation(
        string $localeCode,
        string $localeName,
        string $poContents,
        string $webCatalogContents,
        ?string $base = null
    ): array {
        $publishRepository = $this->publishRepository();
        $pullRequestRepository = $this->pullRequestRepository();
        $base = $base ?: (string) config('torrent.github.publish_base');

        $existingPr = $this->openTranslationPullRequests([$localeCode], $base)[$localeCode] ?? null;

        $localesPath = rtrim((string) config('torrent.github.locales_path'), '/');
        $webLocalesPath = rtrim((string) config('torrent.github.web_locales_path'), '/');
        $indexPath = $webLocalesPath.'/index.json';

        $basePo = $this->readTextFile($pullRequestRepository, $base, $localesPath.'/'.$localeCode.'.po');
        $baseWeb = $this->readJsonFile($pullRequestRepository, $base, $webLocalesPath.'/'.$localeCode.'.json');
        $baseIndex = $this->readJsonFile($pullRequestRepository, $base, $indexPath);

        $desiredWeb = json_decode($webCatalogContents, true);
        $desiredIndex = $this->buildUpdatedIndex($baseIndex, $localeCode, $localeName);

        if (
            $this->normalizedText($basePo) === $this->normalizedText($poContents)
            && $this->normalizedJson($baseWeb) === $this->normalizedJson($desiredWeb)
            && $this->normalizedJson($baseIndex) === $this->normalizedJson($desiredIndex)
        ) {
            return [
                'created' => false,
                'no_changes' => true,
                'number' => $existingPr['number'] ?? null,
                'url' => $existingPr['url'] ?? null,
                'branch' => $existingPr['branch'] ?? null,
            ];
        }

        if ($existingPr) {
            $branch = $existingPr['branch'];
            $created = false;
        } else {
            $refResponse = $this->client()->get(
                "https://api.github.com/repos/{$pullRequestRepository}/git/ref/heads/".rawurlencode($base)
            );

            if (! $refResponse->successful()) {
                throw new RuntimeException(
                    'Falha ao localizar branch base no GitHub: '
                    .$refResponse->status().' - '.$refResponse->body()
                );
            }

            $baseSha = (string) $refResponse->json('object.sha');
            $branch = 'translations/'.strtolower($localeCode).'-'.now()->format('Ymd-His');

            $createRef = $this->client()->post(
                "https://api.github.com/repos/{$publishRepository}/git/refs",
                ['ref' => 'refs/heads/'.$branch, 'sha' => $baseSha]
            );

            if (! $createRef->successful()) {
                throw new RuntimeException(
                    'Falha ao criar branch de tradução: '
                    .$createRef->status().' - '.$createRef->body()
                );
            }

            $created = true;
        }

        $this->writeFile(
            $publishRepository,
            $branch,
            $localesPath.'/'.$localeCode.'.po',
            $poContents,
            'i18n: update '.$localeCode.' translation'
        );

        $this->writeFile(
            $publishRepository,
            $branch,
            $webLocalesPath.'/'.$localeCode.'.json',
            $webCatalogContents,
            'i18n: compile '.$localeCode.' web catalog'
        );

        $index = $this->readJsonFile($publishRepository, $branch, $indexPath);
        $updatedIndex = $this->buildUpdatedIndex($index, $localeCode, $localeName);

        $indexContents = json_encode(
            $updatedIndex,
            JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR
        )."\n";

        $this->writeFile(
            $publishRepository,
            $branch,
            $indexPath,
            $indexContents,
            'i18n: update web language index'
        );

        if ($existingPr) {
            return [
                'created' => false,
                'number' => $existingPr['number'],
                'url' => $existingPr['url'],
                'branch' => $branch,
            ];
        }

        $pr = $this->client()->post(
            "https://api.github.com/repos/{$pullRequestRepository}/pulls",
            [
                'title' => 'i18n: atualização '.$localeCode,
                'head' => $this->crossForkHead($publishRepository, $pullRequestRepository, $branch),
                'base' => $base,
                'body' => "Atualização {$localeCode} gerada pelo Torrent Translate da CIATA.\n\n"
                    ."Inclui o catálogo PO usado no desktop (Windows, macOS e Linux), "
                    ."o JSON compilado para a Web UI e o índice de idiomas.\n\n"
                    ."O merge permanece manual.",
            ]
        );

        if (! $pr->successful()) {
            throw new RuntimeException(
                'Arquivos publicados, mas falhou ao abrir PR: '
                .$pr->status().' - '.$pr->body()
            );
        }

        return [
            'created' => true,
            'number' => (int) $pr->json('number'),
            'url' => (string) $pr->json('html_url'),
            'branch' => $branch,
        ];
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

    private function readTextFile(string $repository, string $ref, string $path): string
    {
        $response = $this->client()->get(
            "https://api.github.com/repos/{$repository}/contents/{$path}",
            ['ref' => $ref]
        );

        if ($response->status() === 404) {
            return '';
        }

        if (! $response->successful()) {
            throw new RuntimeException(
                'Falha ao ler '.$path.' no GitHub: '.$response->status().' - '.$response->body()
            );
        }

        return $this->decodeGitHubContent($response->json('content'));
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

    private function buildUpdatedIndex(array $index, string $localeCode, string $localeName): array
    {
        $languages = [];

        foreach (($index['languages'] ?? []) as $item) {
            if (is_array($item) && filled($item['code'] ?? null) && filled($item['name'] ?? null)) {
                $languages[(string) $item['code']] = (string) $item['name'];
            }
        }

        $languages[$localeCode] = $localeName;
        uksort($languages, 'strnatcasecmp');

        return [
            'languages' => array_map(
                fn (string $code, string $name) => ['code' => $code, 'name' => $name],
                array_keys($languages),
                array_values($languages)
            ),
        ];
    }

    private function normalizedText(string $value): string
    {
        return str_replace(["\r\n", "\r"], "\n", trim($value));
    }

    private function normalizedJson(mixed $value): string
    {
        if (! is_array($value)) {
            return '';
        }

        return json_encode($this->sortRecursively($value), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
    }

    private function sortRecursively(array $value): array
    {
        foreach ($value as $key => $item) {
            if (is_array($item)) {
                $value[$key] = $this->sortRecursively($item);
            }
        }

        if (! array_is_list($value)) {
            ksort($value);
        }

        return $value;
    }

    private function sourceRepository(): string
    {
        return trim((string) config('torrent.github.source_repository'));
    }

    private function publishRepository(): string
    {
        return trim((string) config('torrent.github.publish_repository'));
    }

    private function pullRequestRepository(): string
    {
        return trim((string) config('torrent.github.pull_request_repository'));
    }

    private function crossForkHead(string $publishRepository, string $pullRequestRepository, string $branch): string
    {
        if (strcasecmp($publishRepository, $pullRequestRepository) === 0) {
            return $branch;
        }

        [$owner] = explode('/', $publishRepository, 2);

        return $owner.':'.$branch;
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
