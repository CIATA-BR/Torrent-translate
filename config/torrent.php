<?php

return [
    'admin_emails' => array_values(array_filter(array_map(
        'trim',
        explode(',', (string) env('TORRENT_ADMIN_EMAILS', ''))
    ))),

    'reviewer_emails' => array_values(array_filter(array_map(
        'trim',
        explode(',', (string) env('TORRENT_REVIEWER_EMAILS', ''))
    ))),

    'microsoft_graph' => [
        'tenant_id' => env('MICROSOFT_GRAPH_TENANT_ID'),
        'client_id' => env('MICROSOFT_GRAPH_CLIENT_ID'),
        'client_secret' => env('MICROSOFT_GRAPH_CLIENT_SECRET'),
        'mailbox' => env('MICROSOFT_GRAPH_MAILBOX', env('MICROSOFT_GRAPH_FROM_ADDRESS')),
        'from_name' => env('MICROSOFT_GRAPH_FROM_NAME', env('MAIL_FROM_NAME', 'Torrent Translate')),
        'save_to_sent_items' => filter_var(
            env('MICROSOFT_GRAPH_SAVE_TO_SENT_ITEMS', true),
            FILTER_VALIDATE_BOOL,
            FILTER_NULL_ON_FAILURE
        ) ?? true,
    ],

    'github' => [
        'token' => env('TORRENT_GITHUB_TOKEN'),
        'repository' => env('TORRENT_GITHUB_REPOSITORY', 'CIATA-BR/SerrebiTorrent'),
        'source_ref' => env('TORRENT_GITHUB_SOURCE_REF', 'main'),
        'publish_base' => env('TORRENT_GITHUB_PUBLISH_BASE', 'main'),
        'pot_path' => env('TORRENT_GITHUB_POT_PATH', 'locales/serrebitorrent.pot'),
        'locales_path' => env('TORRENT_GITHUB_LOCALES_PATH', 'locales'),
        'web_locales_path' => env('TORRENT_GITHUB_WEB_LOCALES_PATH', 'web_static/locales'),
    ],
];
