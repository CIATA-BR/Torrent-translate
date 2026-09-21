<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use RuntimeException;

class MicrosoftGraphMailService
{
    public function sendMail(
        string|array $to,
        string $subject,
        string $htmlBody,
        ?string $mailbox = null
    ): void {
        $tenantId = trim((string) config('torrent.microsoft_graph.tenant_id'));
        $clientId = trim((string) config('torrent.microsoft_graph.client_id'));
        $clientSecret = trim((string) config('torrent.microsoft_graph.client_secret'));
        $mailboxToUse = trim((string) ($mailbox ?: config('torrent.microsoft_graph.mailbox')));
        $saveToSentItems = (bool) config('torrent.microsoft_graph.save_to_sent_items', true);

        if ($tenantId === '' || $clientId === '' || $clientSecret === '' || $mailboxToUse === '') {
            throw new RuntimeException('Configuração do Microsoft Graph incompleta.');
        }

        $tokenResponse = Http::asForm()->post(
            "https://login.microsoftonline.com/{$tenantId}/oauth2/v2.0/token",
            [
                'client_id' => $clientId,
                'client_secret' => $clientSecret,
                'scope' => 'https://graph.microsoft.com/.default',
                'grant_type' => 'client_credentials',
            ]
        );

        if (! $tokenResponse->successful()) {
            throw new RuntimeException(
                'Falha ao obter token do Microsoft Graph: '
                .$tokenResponse->status().' - '.$tokenResponse->body()
            );
        }

        $accessToken = trim((string) $tokenResponse->json('access_token'));

        if ($accessToken === '') {
            throw new RuntimeException('Microsoft Graph não retornou access_token.');
        }

        $recipients = is_array($to) ? $to : [$to];

        $payload = [
            'message' => [
                'subject' => $subject,
                'body' => [
                    'contentType' => 'HTML',
                    'content' => $htmlBody,
                ],
                'toRecipients' => collect($recipients)
                    ->filter(fn ($email) => is_string($email) && trim($email) !== '')
                    ->map(fn ($email) => [
                        'emailAddress' => ['address' => trim($email)],
                    ])
                    ->values()
                    ->all(),
            ],
            'saveToSentItems' => $saveToSentItems,
        ];

        $response = Http::withToken($accessToken)
            ->acceptJson()
            ->post(
                'https://graph.microsoft.com/v1.0/users/'.rawurlencode($mailboxToUse).'/sendMail',
                $payload
            );

        if (! $response->successful()) {
            throw new RuntimeException(
                'Falha ao enviar e-mail via Microsoft Graph: '
                .$response->status().' - '.$response->body()
            );
        }
    }
}
