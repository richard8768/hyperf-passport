<?php

declare(strict_types=1);

return [
    'key_store_path' => 'storage',
    'client_uuids' => false,
    'key' => 'CpmLVtjV8diGbhEsVD3IWoVOn31pRpmupEcxMCgtXp9LGpe39F',
    'token_days' => null,
    'refresh_token_days' => null,
    'person_token_days' => null,
    'storage' => [
        'database' => [
            'connection' => env('DB_CONNECTION', 'default'),
        ],
    ],
];
