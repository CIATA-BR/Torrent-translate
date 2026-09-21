<?php

return [
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
];
