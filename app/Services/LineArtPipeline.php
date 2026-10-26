<?php

declare(strict_types=1);

namespace App\Services;

use App\Support\Env;

final class LineArtPipeline
{
    private const MAX_EDGE = 2400;

    public function __construct(private readonly Segmenter $segmenter)
    {
    }

    /** @return array{flats:string,lines:string,provider:string} */
    public function run(string $input, int $seed, ?int $regionHint): array
    {
        if (!class_exists(\Imagick::class)) {
            throw new \RuntimeException('Imagick is required to generate flats on this server.');
        }

        $binary = $this->binarize($this->normalize($input));
        $lines = $this->lineLayer($binary);
        $colors = $this->segmenter->segment($binary, $seed, $regionHint);
        $trapped = $this->trap($colors);
        $flats = $this->stripInk($trapped, $binary);

        return [
            'flats' => $this->pngBytes($flats),
            'lines' => $this->pngBytes($lines),
            'provider' => $this->segmenter instanceof FloodFillSegmenter ? 'flood' : 'segmenter',
        ];
    }

    public static function fromEnv(): self
    {
        $which = strtolower(Env::get('SEGMENTER', 'flood') ?? 'flood');
        $segmenter = $which === 'sam' ? new SamSegmenter() : new FloodFillSegmenter();

        return new self($segmenter);
    }

    private function normalize(string $input): \Imagick
    {
        $im = new \Imagick();
        $im->readImageBlob($input);
        if (method_exists($im, 'autoOrient')) {
            $im->autoOrient();
        }
        $im->setImageFormat('png');
        $w = $im->getImageWidth();
        $h = $im->getImageHeight();
        $long = max($w, $h);
        if ($long > self::MAX_EDGE) {
            $scale = self::MAX_EDGE / $long;
            $im->resizeImage(
                max(1, (int) round($w * $scale)),
                max(1, (int) round($h * $scale)),
                \Imagick::FILTER_LANCZOS,
                1,
            );
        }
        $im->setImageInterpolateMethod(\Imagick::INTERPOLATE_INTEGER);

        return $im;
    }

    private function binarize(\Imagick $im): \Imagick
    {
        $im->transformImageColorspace(\Imagick::COLORSPACE_GRAY);
        $im->thresholdImage(0.45 * \Imagick::getQuantum());
        $im->setImageFormat('png');

        return $im;
    }

    private function lineLayer(\Imagick $binary): \Imagick
    {
        $lines = clone $binary;
        $lines->setImageAlphaChannel(\Imagick::ALPHACHANNEL_ACTIVATE);
        $lines->transparentPaintImage('#ffffff', 0.0, 0.0, false);
        $lines->setImageFormat('png');

        return $lines;
    }

    private function trap(\Imagick $colors): \Imagick
    {
        $spread = $this->spread($colors);
        $spread = $this->spread($spread);
        $spread->setImageInterpolateMethod(\Imagick::INTERPOLATE_INTEGER);

        return $spread;
    }

    /** Copy an exact neighbor color into transparent pixels. No blending. */
    private function spread(\Imagick $colors): \Imagick
    {
        $w = $colors->getImageWidth();
        $h = $colors->getImageHeight();
        $rgba = $colors->exportImagePixels(0, 0, $w, $h, 'RGBA', \Imagick::PIXEL_CHAR);
        if (!is_array($rgba)) {
            throw new \RuntimeException('Could not trap flats.');
        }
        $next = $rgba;
        $n = $w * $h;
        for ($i = 0; $i < $n; $i++) {
            if (((int) $rgba[$i * 4 + 3]) > 16) {
                continue;
            }
            $x = $i % $w;
            $y = intdiv($i, $w);
            foreach ([[1, 0], [-1, 0], [0, 1], [0, -1]] as [$dx, $dy]) {
                $nx = $x + $dx;
                $ny = $y + $dy;
                if ($nx < 0 || $ny < 0 || $nx >= $w || $ny >= $h) {
                    continue;
                }
                $o = ($ny * $w + $nx) * 4;
                if (((int) $rgba[$o + 3]) < 16) {
                    continue;
                }
                $t = $i * 4;
                $next[$t] = $rgba[$o];
                $next[$t + 1] = $rgba[$o + 1];
                $next[$t + 2] = $rgba[$o + 2];
                $next[$t + 3] = 255;
                break;
            }
        }
        $colors->importImagePixels(0, 0, $w, $h, 'RGBA', \Imagick::PIXEL_CHAR, $next);

        return $colors;
    }

    private function stripInk(\Imagick $colors, \Imagick $binary): \Imagick
    {
        $w = $colors->getImageWidth();
        $h = $colors->getImageHeight();
        $ink = $binary->exportImagePixels(0, 0, $w, $h, 'I', \Imagick::PIXEL_CHAR);
        $rgba = $colors->exportImagePixels(0, 0, $w, $h, 'RGBA', \Imagick::PIXEL_CHAR);
        if (!is_array($ink) || !is_array($rgba)) {
            throw new \RuntimeException('Could not composite flats.');
        }

        $n = $w * $h;
        for ($i = 0; $i < $n; $i++) {
            if (((int) $ink[$i]) < 128) {
                $o = $i * 4;
                if (((int) $rgba[$o + 3]) < 16) {
                    $rgba[$o] = 0;
                    $rgba[$o + 1] = 0;
                    $rgba[$o + 2] = 0;
                    $rgba[$o + 3] = 0;
                }
            }
        }

        $colors->importImagePixels(0, 0, $w, $h, 'RGBA', \Imagick::PIXEL_CHAR, $rgba);
        $colors->setImageFormat('png');

        return $colors;
    }

    private function pngBytes(\Imagick $im): string
    {
        $im->setImageFormat('png');
        $blob = $im->getImagesBlob();
        $im->clear();

        return $blob;
    }
}
