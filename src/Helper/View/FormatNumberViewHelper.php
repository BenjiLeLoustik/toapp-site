<?php

namespace App\Helper\View;

use NeoPHP\Component\View\Contract\ViewFilterInterface;

class FormatNumberViewHelper implements ViewFilterInterface
{

    public function getName(): string
    {
        return 'format_number';
    }

    public function __invoke(int|float $number): string
    {
        if ($number < 1000) {
            return (string)$number;
        }

        if ($number < 1_000_000) {
            $value = $number / 1000;

            return $this->formatNumber($value, 'k');
        }

        if ($number < 1_000_000_000) {
            $value = $number / 1_000_000;

            return $this->formatNumber($value, 'M');
        }

        $value = $number / 1_000_000_000;

        return $this->formatNumber($value, 'B');
    }

    private function formatNumber(int|float $number, string $suffix): string
    {
        $value = number_format($number, 2, '.', '');
        $value = rtrim($value, '0');
        $value = rtrim($value, '.');
        return $value . $suffix;
    }
}