<?php

declare(strict_types=1);

return [
    'session_user_login_uri' => '/user_login',
    'key_store_path' => 'storage',
    'client_uuids' => false,
    'key' => 'E3Wxizr8gUXuBuyG7CecmGX9E9lbRzdFmqQpG2yP85eDuXzqOj',
    'token_days' => null,
    'refresh_token_days' => null,
    'person_token_days' => null,
    'database_connection' => env('DB_CONNECTION', 'default'),
    'is_revoke_user_others_token' => true,//when user login if revoke user's all token except current one
];
