<?php

declare(strict_types=1);

namespace App\Security;

use App\Support\Env;

final class Session
{
    public static function start(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            return;
        }

        $secure = str_starts_with(Env::get('APP_URL', 'http://') ?? 'http://', 'https://');
        session_set_cookie_params([
            'lifetime' => 0,
            'path' => '/',
            'secure' => $secure,
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
        ini_set('session.use_strict_mode', '1');
        ini_set('session.use_only_cookies', '1');
        session_name('comicflats_session');
        session_start();
    }

    public static function regenerate(): void
    {
        session_regenerate_id(true);
    }
}
