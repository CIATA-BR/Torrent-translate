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

        $content = str_replace("\n", '', (string) $response->json('content'));
        $decoded = base64_decode($content, true);

        if ($decoded === false) {
            throw new RuntimeException('Conteúdo POT retornado pelo GitHub é inválido.');
        }

        return $decoded;
    }

    public function publishPo(string $localeCode, string $poContents, ?string $base = null): string
    {
        $repository = (string) config('torrent.github.repository');
        $base = $base ?: (string) config('torrent.github.publish_base');
        $path = rtrim((string) config('torrent.github.locales_path'), '/').'/'.$localeCode.'.po';

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
            [
                'ref' => 'refs/heads/'.$branch,
                'sha' => $baseSha,
            ]
        );

        if (! $createRef->successful()) {
            throw new RuntimeException(
                'Falha ao criar branch de tradução: '.$createRef->status().' - '.$createRef->body()
            );
        }

        $existing = $this->client()->get(
            "https://api.github.com/repos/{$repository}/contents/{$path}",
            ['ref' => $branch]
        );

        $payload = [
            'message' => 'i18n: update '.$localeCode.' translation',
            'content' => base64_encode($poContents),
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
                'Falha ao gravar catálogo no GitHub: '.$write->status().' - '.$write->body()
            );
        }

        $pr = $this->client()->post(
            "https://api.github.com/repos/{$repository}/pulls",
            [
                'title' => 'i18n: atualização '.$localeCode,
                'head' => $branch,
                'base' => $base,
                'body' => "Atualização do catálogo {$localeCode} gerada pelo Torrent Translate da CIATA.\n\nO merge permanece manual.",
            ]
        );

        if (! $pr->successful()) {
            throw new RuntimeException(
                'Arquivo publicado, mas falhou ao abrir PR: '.$pr->status().' - '.$pr->body()
            );
        }

        return (string) $pr->json('html_url');
    }
}
