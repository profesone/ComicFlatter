<?php

declare(strict_types=1);

use App\Security\Session;
use App\Support\Env;

$root = dirname(__DIR__);

require $root . '/vendor/autoload.php';

Env::load($root . '/.env');
Session::start();

header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: strict-origin-when-cross-origin');
header('X-Frame-Options: SAMEORIGIN');
header("Content-Security-Policy: default-src 'self'; img-src 'self' data: blob:; style-src 'self' https://fonts.googleapis.com; font-src 'self' https://fonts.gstatic.com; script-src 'self'; form-action 'self' https://checkout.stripe.com; frame-ancestors 'none'; base-uri 'self'");

function e(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}
