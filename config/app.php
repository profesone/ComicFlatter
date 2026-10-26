<?php

declare(strict_types=1);

return [
    'name' => 'ComicFlats',
    'price_cents' => 499,
    'max_attempts' => 4,
    'max_upload_bytes' => 20 * 1024 * 1024,
    'job_ttl_hours' => 48,
    'allowed_mimes' => ['image/png', 'image/jpeg', 'image/webp', 'image/tiff'],
];
