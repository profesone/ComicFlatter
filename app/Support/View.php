<?php

declare(strict_types=1);

namespace App\Support;

final class View
{
    public static function render(string $template, array $data = []): void
    {
        $path = dirname(__DIR__, 2) . '/templates/' . $template . '.php';
        if (!is_file($path)) {
            http_response_code(500);
            echo 'Template missing';
            return;
        }
        extract($data, EXTR_SKIP);
        $user = $_SESSION['user'] ?? null;
        require dirname(__DIR__, 2) . '/templates/layout.php';
    }
}
