<?php

declare(strict_types=1);

namespace App\Services;

use App\Support\Env;

final class Storage
{
    public static function root(): string
    {
        $dir = Env::get('STORAGE_DIR', 'storage/uploads') ?? 'storage/uploads';
        $path = str_starts_with($dir, '/') ? $dir : dirname(__DIR__, 2) . '/' . $dir;
        return $path;
    }

    public static function save(string $relativeDir, string $bytes, string $ext): string
    {
        $name = bin2hex(random_bytes(8)) . '.' . ltrim($ext, '.');
        $dir = self::root() . '/' . trim($relativeDir, '/');
        if (!is_dir($dir) && !mkdir($dir, 0755, true) && !is_dir($dir)) {
            throw new \RuntimeException('Could not create upload directory');
        }
        $relative = trim($relativeDir, '/') . '/' . $name;
        $absolute = self::root() . '/' . $relative;
        if (file_put_contents($absolute, $bytes) === false) {
            throw new \RuntimeException('Could not store file');
        }
        return $relative;
    }

    public static function absolute(string $relative): ?string
    {
        $relative = ltrim(str_replace('\\', '/', $relative), '/');
        if ($relative === '' || str_contains($relative, '..')) {
            return null;
        }
        $root = realpath(self::root());
        if ($root === false) {
            return null;
        }
        $absolute = realpath($root . '/' . $relative);
        if ($absolute === false || !str_starts_with($absolute, $root . DIRECTORY_SEPARATOR) && $absolute !== $root) {
            return null;
        }
        return $absolute;
    }
}
