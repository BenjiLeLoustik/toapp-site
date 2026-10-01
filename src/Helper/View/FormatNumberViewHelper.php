<?php

namespace App\Helper\View;

use NeoPHP\Component\View\Contract\ViewFilterInterface;

class FormatNumberViewHelper implements ViewFilterInterface
{

    public function getName(): string
    {
        return 'format_number';
    }

    public function __invoke(int|float $number, int $precision = 1): string
    {
        if ($number < 1000) {
            return (string)$number;
        }

        if ($number < 1_000_000) {
            $value = $number / 1000;

            return $this->formatNumber($value, 'k', $precision);
        }

        if ($number < 1_000_000_000) {
            $value = $number / 1_000_000;

            return $this->formatNumber($value, 'M', $precision);
        }

        $value = $number / 1_000_000_000;

        return $this->formatNumber($value, 'B', $precision);
    }

    private function formatNumber(int|float $number, string $suffix, int $precision): string
    {
        $value = number_format($number, $precision, '.', '');
        $value = rtrim($value, '0');
        $value = rtrim($value, '.');
        return $value . $suffix;
    }
}