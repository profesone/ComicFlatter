<?php

declare(strict_types=1);

namespace App\Services;

use App\Support\Env;

final class Mailer
{
    public static function send(string $to, string $subject, string $body): bool
    {
        $from = Env::get('MAIL_FROM', 'ComicFlats <noreply@localhost>') ?? 'ComicFlats <noreply@localhost>';
        $headers = "From: {$from}\r\nContent-Type: text/plain; charset=UTF-8";
        return mail($to, $subject, $body, $headers);
    }
}
