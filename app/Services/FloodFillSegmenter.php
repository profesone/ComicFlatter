<?php

declare(strict_types=1);

namespace App\Services;

/** 4-connected flood fill of white regions inside binarized line art. */
final class FloodFillSegmenter implements Segmenter
{
    private const MIN_PIXELS = 24;

    public function segment(\Imagick $binary, int $seed, ?int $regionHint): \Imagick
    {
        $w = $binary->getImageWidth();
        $h = $binary->getImageHeight();
        $gray = $binary->exportImagePixels(0, 0, $w, $h, 'I', \Imagick::PIXEL_CHAR);
        if (!is_array($gray)) {
            throw new \RuntimeException('Could not read line art pixels.');
        }

        $n = $w * $h;
        $labels = array_fill(0, $n, 0);
        $ink = array_fill(0, $n, false);
        for ($i = 0; $i < $n; $i++) {
            $ink[$i] = ((int) $gray[$i]) < 128;
        }

        $regions = [];
        $next = 1;
        $visited = array_fill(0, $n, false);

        for ($start = 0; $start < $n; $start++) {
            if ($visited[$start] || $ink[$start]) {
                continue;
            }
            $queue = [$start];
            $visited[$start] = true;
            $pixels = [$start];
            $sumX = $start % $w;
            $sumY = intdiv($start, $w);
            $head = 0;
            while ($head < count($queue)) {
                $i = $queue[$head++];
                $x = $i % $w;
                $y = intdiv($i, $w);
                foreach ([[1, 0], [-1, 0], [0, 1], [0, -1]] as [$dx, $dy]) {
                    $nx = $x + $dx;
                    $ny = $y + $dy;
                    if ($nx < 0 || $ny < 0 || $nx >= $w || $ny >= $h) {
                        continue;
                    }
                    $ni = $ny * $w + $nx;
                    if ($visited[$ni] || $ink[$ni]) {
                        continue;
                    }
                    $visited[$ni] = true;
                    $queue[] = $ni;
                    $pixels[] = $ni;
                    $sumX += $nx;
                    $sumY += $ny;
                }
            }
            $count = count($pixels);
            if ($count < self::MIN_PIXELS) {
                continue;
            }
            foreach ($pixels as $pi) {
                $labels[$pi] = $next;
            }
            $regions[$next] = [
                'count' => $count,
                'cx' => $sumX / $count,
                'cy' => $sumY / $count,
            ];
            $next++;
        }

        $labels = $this->capRegions($labels, $regions, $regionHint);
        $shift = $seed % 360;

        $rgba = [];
        for ($i = 0; $i < $n; $i++) {
            $label = $labels[$i];
            if ($label <= 0) {
                $rgba[] = 0;
                $rgba[] = 0;
                $rgba[] = 0;
                $rgba[] = 0;
                continue;
            }
            [$r, $g, $b] = $this->hsv(fmod(($label * 137.508) + $shift, 360.0), 0.78, 0.95);
            $rgba[] = $r;
            $rgba[] = $g;
            $rgba[] = $b;
            $rgba[] = 255;
        }

        $out = new \Imagick();
        $out->newImage($w, $h, new \ImagickPixel('transparent'));
        $out->setImageFormat('png');
        $out->setImageAlphaChannel(\Imagick::ALPHACHANNEL_ACTIVATE);
        $out->setImageInterpolateMethod(\Imagick::INTERPOLATE_INTEGER);
        $out->importImagePixels(0, 0, $w, $h, 'RGBA', \Imagick::PIXEL_CHAR, $rgba);

        return $out;
    }

    /** @param array<int, array{count:int,cx:float,cy:float}> $regions */
    private function capRegions(array $labels, array &$regions, ?int $regionHint): array
    {
        if ($regionHint === null || $regionHint < 2 || count($regions) <= $regionHint) {
            return $labels;
        }

        uasort($regions, static fn (array $a, array $b): int => $b['count'] <=> $a['count']);
        $keep = array_slice(array_keys($regions), 0, $regionHint, true);
        $keepSet = array_fill_keys($keep, true);
        $keptMeta = [];
        foreach ($keep as $id) {
            $keptMeta[$id] = $regions[$id];
        }

        $remap = [];
        foreach ($regions as $id => $meta) {
            if (isset($keepSet[$id])) {
                $remap[$id] = $id;
                continue;
            }
            $best = $keep[0];
            $bestDist = PHP_FLOAT_MAX;
            foreach ($keptMeta as $kid => $kmeta) {
                $dx = $meta['cx'] - $kmeta['cx'];
                $dy = $meta['cy'] - $kmeta['cy'];
                $dist = ($dx * $dx) + ($dy * $dy);
                if ($dist < $bestDist) {
                    $bestDist = $dist;
                    $best = $kid;
                }
            }
            $remap[$id] = $best;
        }

        $regions = $keptMeta;
        foreach ($labels as $i => $label) {
            if ($label > 0 && isset($remap[$label])) {
                $labels[$i] = $remap[$label];
            }
        }

        return $labels;
    }

    /** @return array{0:int,1:int,2:int} */
    private function hsv(float $h, float $s, float $v): array
    {
        $c = $v * $s;
        $x = $c * (1 - abs(fmod($h / 60.0, 2) - 1));
        $m = $v - $c;
        $r = 0.0;
        $g = 0.0;
        $b = 0.0;
        if ($h < 60) {
            [$r, $g, $b] = [$c, $x, 0.0];
        } elseif ($h < 120) {
            [$r, $g, $b] = [$x, $c, 0.0];
        } elseif ($h < 180) {
            [$r, $g, $b] = [0.0, $c, $x];
        } elseif ($h < 240) {
            [$r, $g, $b] = [0.0, $x, $c];
        } elseif ($h < 300) {
            [$r, $g, $b] = [$x, 0.0, $c];
        } else {
            [$r, $g, $b] = [$c, 0.0, $x];
        }

        return [
            (int) round(($r + $m) * 255),
            (int) round(($g + $m) * 255),
            (int) round(($b + $m) * 255),
        ];
    }
}
