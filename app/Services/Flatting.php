<?php

declare(strict_types=1);

namespace App\Services;

/** Local line-art flatting. SEGMENTER=sam is reserved for a future GPU hook. */
final class Flatting
{
    /** @return array{flats:string,lines:string,provider:string} */
    public static function generate(string $inputBytes, int $seed, ?int $regionHint): array
    {
        return LineArtPipeline::fromEnv()->run($inputBytes, $seed, $regionHint);
    }
}
