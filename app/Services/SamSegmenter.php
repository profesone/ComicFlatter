<?php

declare(strict_types=1);

namespace App\Services;

/**
 * Later hook: POST the binarized PNG to a SAM 2 (or similar) GPU service
 * and import the returned label map. Not called while SEGMENTER=flood.
 */
final class SamSegmenter implements Segmenter
{
    public function segment(\Imagick $binary, int $seed, ?int $regionHint): \Imagick
    {
        throw new \RuntimeException('SAM 2 segmentation is not configured. Set SEGMENTER=flood.');
    }
}
