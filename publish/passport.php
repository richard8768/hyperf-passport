<?php

declare(strict_types=1);

return [
    'session_user_login_uri' => env('PASSPORT_SESSION_USER_LOGIN_URL', '/user_login'),
    'key_store_path' => 'storage',
    'client_uuids' => false,
    'key' => env('PASSPORT_KEY', ''),
    'token_days' => null,
    'refresh_token_days' => null,
    'person_token_days' => null,
    'database_connection' => env('DB_CONNECTION', 'default'),
    'is_revoke_user_others_token' => true, //when user login if revoke user's all token except current one
];
