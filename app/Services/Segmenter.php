<?php

declare(strict_types=1);

namespace App\Services;

interface Segmenter
{
    /**
     * Color enclosed non-ink regions. Ink pixels stay transparent.
     * Edges must be hard (no anti-aliasing).
     */
    public function segment(\Imagick $binary, int $seed, ?int $regionHint): \Imagick;
}
