<?php

return [
    'microsoft_graph' => [
        'tenant_id' => env('MICROSOFT_GRAPH_TENANT_ID'),
        'client_id' => env('MICROSOFT_GRAPH_CLIENT_ID'),
        'client_secret' => env('MICROSOFT_GRAPH_CLIENT_SECRET'),
        'mailbox' => env('MICROSOFT_GRAPH_MAILBOX'),
        'from_name' => env('MICROSOFT_GRAPH_FROM_NAME', 'Torrent Translate'),
    ],
];
