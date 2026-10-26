<?php

declare(strict_types=1);

namespace App\Services;

final class ComicLetterer
{
    private const ZONES = [
        'top-left' => [0.17, 0.17],
        'top-center' => [0.5, 0.17],
        'top-right' => [0.83, 0.17],
        'middle-left' => [0.17, 0.5],
        'middle-center' => [0.5, 0.5],
        'middle-right' => [0.83, 0.5],
        'bottom-left' => [0.17, 0.83],
        'bottom-center' => [0.5, 0.83],
        'bottom-right' => [0.83, 0.83],
    ];

    public function render(string $pagePath, int $panelCount, array $lines): string
    {
        if (!class_exists(\Imagick::class)) {
            throw new \RuntimeException('Imagick is required to draw balloons.');
        }

        $page = new \Imagick($pagePath);
        $width = $page->getImageWidth();
        $height = $page->getImageHeight();
        $canvas = new \Imagick();
        $canvas->newImage($width, $height, new \ImagickPixel('transparent'));
        $canvas->setImageFormat('png');

        $font = dirname(__DIR__, 2) . '/public/assets/fonts/lettering.ttf';
        $font = is_file($font) ? $font : null;

        foreach ($lines as $line) {
            $text = trim((string) ($line['text'] ?? ''));
            if ($text === '') {
                continue;
            }
            $style = (string) ($line['style'] ?? 'Standard');
            $panel = max(0, ((int) ($line['panel'] ?? 1)) - 1);
            $rect = $this->panelRect($panel, max(1, min(9, $panelCount)), $width, $height);
            $zone = (string) ($line['balloonZone'] ?? 'top-left');
            [$cx, $cy] = $this->nudge($page, $rect, $zone);
            $speaker = (string) ($line['speakerZone'] ?? 'off-panel');
            $this->drawBalloon($canvas, $font, $text, $style, $cx, $cy, $rect, $speaker, $width, $height);
        }

        $blob = $canvas->getImagesBlob();
        $canvas->clear();
        $page->clear();

        return $blob;
    }

    /** @return array{0:int,1:int,2:int,3:int} */
    private function panelRect(int $index, int $count, int $width, int $height): array
    {
        [$cols, $rows] = match (true) {
            $count <= 1 => [1, 1],
            $count === 2 => [2, 1],
            $count <= 4 => [2, 2],
            $count <= 6 => [3, 2],
            default => [3, 3],
        };
        $index = max(0, min($index, ($cols * $rows) - 1));
        $col = $index % $cols;
        $row = intdiv($index, $cols);
        $pw = (int) floor($width / $cols);
        $ph = (int) floor($height / $rows);

        return [$col * $pw, $row * $ph, $pw, $ph];
    }

    /** @param array{0:int,1:int,2:int,3:int} $rect */
    private function nudge(\Imagick $page, array $rect, string $zone): array
    {
        [$x, $y, $w, $h] = $rect;
        [$fx, $fy] = self::ZONES[$zone] ?? self::ZONES['top-left'];
        $zx = $x + (int) (($fx - 0.16) * $w);
        $zy = $y + (int) (($fy - 0.16) * $h);
        $zw = max(8, (int) ($w * 0.33));
        $zh = max(8, (int) ($h * 0.33));
        $zx = max($x, min($x + $w - $zw, $zx));
        $zy = max($y, min($y + $h - $zh, $zy));

        $best = PHP_INT_MAX;
        $bx = $zx + intdiv($zw, 2);
        $by = $zy + intdiv($zh, 2);
        $stepX = max(1, intdiv($zw, 3));
        $stepY = max(1, intdiv($zh, 3));
        for ($row = 0; $row < 3; $row++) {
            for ($col = 0; $col < 3; $col++) {
                $px = $zx + $col * $stepX;
                $py = $zy + $row * $stepY;
                $pw = min($stepX, $x + $w - $px);
                $ph = min($stepY, $y + $h - $py);
                if ($pw < 2 || $ph < 2) {
                    continue;
                }
                $dark = $this->darkCount($page, $px, $py, $pw, $ph);
                if ($dark < $best) {
                    $best = $dark;
                    $bx = $px + intdiv($pw, 2);
                    $by = $py + intdiv($ph, 2);
                }
            }
        }

        return [$bx, $by];
    }

    private function darkCount(\Imagick $page, int $x, int $y, int $w, int $h): int
    {
        $pixels = $page->exportImagePixels($x, $y, $w, $h, 'I', \Imagick::PIXEL_CHAR);
        if (!is_array($pixels)) {
            return 0;
        }
        $dark = 0;
        foreach ($pixels as $value) {
            if ((int) $value < 140) {
                $dark++;
            }
        }

        return $dark;
    }

    /** @param array{0:int,1:int,2:int,3:int} $rect */
    private function drawBalloon(
        \Imagick $canvas,
        ?string $font,
        string $text,
        string $style,
        int $cx,
        int $cy,
        array $rect,
        string $speaker,
        int $pageW,
        int $pageH,
    ): void {
        $draw = new \ImagickDraw();
        if ($font) {
            $draw->setFont($font);
        }
        $size = $style === 'Whisper' ? 22 : ($style === 'SFX' ? 42 : 28);
        $draw->setFontSize($size);
        $draw->setTextAlignment(\Imagick::ALIGN_CENTER);
        $maxWidth = max(80, (int) ($rect[2] * ($style === 'Burst' ? 0.55 : 0.42)));
        $wrapped = $this->wrap($canvas, $draw, $text, $maxWidth);
        $metrics = $canvas->queryFontMetrics($draw, $wrapped);
        $textW = (int) ceil($metrics['textWidth']);
        $textH = (int) ceil($metrics['textHeight']);
        $padX = $style === 'Whisper' ? 18 : 28;
        $padY = $style === 'Whisper' ? 14 : 22;
        $rx = (int) ($textW / 2 + $padX);
        $ry = (int) ($textH / 2 + $padY);

        if ($style === 'SFX') {
            $draw->setFillColor('#fe9179');
            $draw->setStrokeColor('#053229');
            $draw->setStrokeWidth(6);
            $draw->translate($cx, $cy);
            $draw->rotate(-12);
            $draw->annotation(0, (int) ($textH / 4), $wrapped);
            $canvas->drawImage($draw);
            return;
        }

        $draw->setFillColor('white');
        $draw->setStrokeColor('#053229');
        $draw->setStrokeWidth($style === 'Whisper' ? 2 : 5);
        if ($style === 'Burst') {
            $draw->polygon($this->burstPoints($cx, $cy, $rx, $ry));
        } elseif ($style === 'Wavy') {
            $draw->polygon($this->wavyPoints($cx, $cy, $rx, $ry));
        } else {
            $draw->ellipse($cx, $cy, $rx, $ry, 0, 360);
        }
        $canvas->drawImage($draw);

        {
            $tail = new \ImagickDraw();
            $tail->setFillColor('white');
            $tail->setStrokeColor('white');
            $tail->setStrokeWidth(0);
            [$tx, $ty] = $this->tailTarget($speaker, $rect, $cx, $cy, $pageW, $pageH);
            $points = $this->tailPoints($cx, $cy, $rx, $ry, $tx, $ty);
            $tail->polygon($points);
            $canvas->drawImage($tail);

            $outline = new \ImagickDraw();
            $outline->setFillColor('none');
            $outline->setStrokeColor('#053229');
            $outline->setStrokeWidth($style === 'Whisper' ? 2 : 5);
            $outline->line($points[0]['x'], $points[0]['y'], $points[2]['x'], $points[2]['y']);
            $outline->line($points[1]['x'], $points[1]['y'], $points[2]['x'], $points[2]['y']);
            $canvas->drawImage($outline);
        }

        $letters = new \ImagickDraw();
        if ($font) {
            $letters->setFont($font);
        }
        $letters->setFontSize($size);
        $letters->setFillColor('#053229');
        $letters->setStrokeWidth(0);
        $letters->setTextAlignment(\Imagick::ALIGN_CENTER);
        $letters->annotation($cx, $cy + (int) ($textH / 4), $wrapped);
        $canvas->drawImage($letters);
    }

    private function wrap(\Imagick $image, \ImagickDraw $draw, string $text, int $maxWidth): string
    {
        $words = preg_split('/\s+/', $text) ?: [$text];
        $lines = [];
        $current = '';
        foreach ($words as $word) {
            $trial = $current === '' ? $word : $current . ' ' . $word;
            $width = $image->queryFontMetrics($draw, $trial)['textWidth'];
            if ($width > $maxWidth && $current !== '') {
                $lines[] = $current;
                $current = $word;
            } else {
                $current = $trial;
            }
        }
        if ($current !== '') {
            $lines[] = $current;
        }

        return implode("\n", $lines);
    }

    /** @return list<array{x:float,y:float}> */
    private function burstPoints(int $cx, int $cy, int $rx, int $ry): array
    {
        $points = [];
        for ($i = 0; $i < 18; $i++) {
            $angle = $i * (M_PI * 2 / 18);
            $scale = $i % 2 === 0 ? 1.25 : 0.82;
            $points[] = [
                'x' => $cx + cos($angle) * $rx * $scale,
                'y' => $cy + sin($angle) * $ry * $scale,
            ];
        }

        return $points;
    }

    /** @return list<array{x:float,y:float}> */
    private function wavyPoints(int $cx, int $cy, int $rx, int $ry): array
    {
        $points = [];
        $steps = 28;
        for ($i = 0; $i < $steps; $i++) {
            $angle = $i * (M_PI * 2 / $steps);
            $scale = 1 + 0.08 * sin($i * 3);
            $points[] = [
                'x' => $cx + cos($angle) * $rx * $scale,
                'y' => $cy + sin($angle) * $ry * $scale,
            ];
        }

        return $points;
    }

    /** @param array{0:int,1:int,2:int,3:int} $rect */
    private function tailTarget(string $speaker, array $rect, int $cx, int $cy, int $pageW, int $pageH): array
    {
        if ($speaker === 'off-panel') {
            $edges = [
                [$cx, 8],
                [$pageW - 8, $cy],
                [$cx, $pageH - 8],
                [8, $cy],
            ];
            $best = $edges[0];
            $bestD = PHP_FLOAT_MAX;
            foreach ($edges as $edge) {
                $d = hypot($edge[0] - $cx, $edge[1] - $cy);
                if ($d < $bestD) {
                    $bestD = $d;
                    $best = $edge;
                }
            }

            return $best;
        }
        [$x, $y, $w, $h] = $rect;
        [$fx, $fy] = self::ZONES[$speaker] ?? [0.5, 0.85];

        return [(int) ($x + $fx * $w), (int) ($y + $fy * $h)];
    }

    /** @return list<array{x:float,y:float}> */
    private function tailPoints(int $cx, int $cy, int $rx, int $ry, int $tx, int $ty): array
    {
        $angle = atan2($ty - $cy, $tx - $cx);
        $base = 12;
        $inset = 0.72;

        return [
            [
                'x' => $cx + cos($angle) * $rx * $inset + cos($angle + M_PI_2) * $base,
                'y' => $cy + sin($angle) * $ry * $inset + sin($angle + M_PI_2) * $base,
            ],
            [
                'x' => $cx + cos($angle) * $rx * $inset + cos($angle - M_PI_2) * $base,
                'y' => $cy + sin($angle) * $ry * $inset + sin($angle - M_PI_2) * $base,
            ],
            ['x' => $tx, 'y' => $ty],
        ];
    }
}
