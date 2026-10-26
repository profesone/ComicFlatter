<?php

declare(strict_types=1);

namespace App\Services;

final class Images
{
    /** @return array{ok:true,mime:string,ext:string}|array{ok:false,error:string} */
    public static function validate(string $bytes, int $maxBytes): array
    {
        if (strlen($bytes) > $maxBytes) {
            return ['ok' => false, 'error' => 'File too large. Max size is 20MB.'];
        }
        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mime = $finfo->buffer($bytes) ?: '';
        $map = [
            'image/png' => 'png',
            'image/jpeg' => 'jpg',
            'image/webp' => 'webp',
            'image/tiff' => 'tiff',
        ];
        if (!isset($map[$mime])) {
            return ['ok' => false, 'error' => 'Unsupported file type. Use PNG, JPG, TIFF, or WEBP.'];
        }
        $info = @getimagesizefromstring($bytes);
        if ($info === false) {
            return ['ok' => false, 'error' => 'Could not read this image.'];
        }
        [$w, $h] = $info;
        if ($w < 256 || $h < 256) {
            return ['ok' => false, 'error' => 'Image too small. Please upload at least 256×256 pixels.'];
        }
        if ($w > 8000 || $h > 8000) {
            return ['ok' => false, 'error' => 'Image too large. Keep each side under 8000px.'];
        }
        return ['ok' => true, 'mime' => $mime, 'ext' => $map[$mime]];
    }
}
