<?php

namespace App\Support;

use InvalidArgumentException;

class Ean13Barcode
{
    private const L = [
        '0001101', '0011001', '0010011', '0111101', '0100011',
        '0110001', '0101111', '0111011', '0110111', '0001011',
    ];

    private const G = [
        '0100111', '0110011', '0011011', '0100001', '0011101',
        '0111001', '0000101', '0010001', '0001001', '0010111',
    ];

    private const R = [
        '1110010', '1100110', '1101100', '1000010', '1011100',
        '1001110', '1010000', '1000100', '1001000', '1110100',
    ];

    private const PARITY = [
        'LLLLLL', 'LLGLGG', 'LLGGLG', 'LLGGGL', 'LGLLGG',
        'LGGLLG', 'LGGGLL', 'LGLGLG', 'LGLGGL', 'LGGLGL',
    ];

    public static function svg(string $value, int $height = 70): string
    {
        if (! preg_match('/^\d{13}$/', $value) || ! self::hasValidChecksum($value)) {
            throw new InvalidArgumentException('The barcode must be a valid EAN-13 value.');
        }

        $digits = array_map('intval', str_split($value));
        $bits = '101';
        $parity = self::PARITY[$digits[0]];

        for ($index = 1; $index <= 6; $index++) {
            $bits .= $parity[$index - 1] === 'L'
                ? self::L[$digits[$index]]
                : self::G[$digits[$index]];
        }

        $bits .= '01010';

        for ($index = 7; $index <= 12; $index++) {
            $bits .= self::R[$digits[$index]];
        }

        $bits .= '101';
        $module = 2;
        $quietZone = 10;
        $barcodeWidth = strlen($bits) * $module;
        $width = $barcodeWidth + ($quietZone * 2);
        $bars = '';

        foreach (str_split($bits) as $index => $bit) {
            if ($bit !== '1') {
                continue;
            }

            $isGuard = $index < 3 || ($index >= 45 && $index < 50) || $index >= 92;
            $barHeight = $isGuard ? $height + 6 : $height;
            $x = $quietZone + ($index * $module);
            $bars .= sprintf('<rect x="%d" y="0" width="%d" height="%d" />', $x, $module, $barHeight);
        }

        $escaped = htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
        $totalHeight = $height + 24;

        return <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" width="{$width}" height="{$totalHeight}" viewBox="0 0 {$width} {$totalHeight}" role="img" aria-label="EAN-13 {$escaped}">
    <rect width="100%" height="100%" fill="#fff"/>
    <g fill="#111">{$bars}</g>
    <text x="50%" y="{$totalHeight}" text-anchor="middle" font-family="monospace" font-size="14" letter-spacing="2">{$escaped}</text>
</svg>
SVG;
    }

    private static function hasValidChecksum(string $value): bool
    {
        $sum = 0;

        foreach (str_split(substr($value, 0, 12)) as $index => $digit) {
            $sum += (int) $digit * ($index % 2 === 0 ? 1 : 3);
        }

        return (int) $value[12] === (10 - ($sum % 10)) % 10;
    }
}
