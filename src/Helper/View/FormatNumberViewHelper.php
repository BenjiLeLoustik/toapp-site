<?php

declare(strict_types=1);

namespace App\Helper\View;

use App\User\Helper\UserFormatHelper;
use NeoPHP\Component\View\Contract\ViewFilterInterface;

class FormatNumberViewHelper implements ViewFilterInterface
{
    public function __construct(private UserFormatHelper $format)
    {
    }

    public function getName(): string
    {
        return 'format_number';
    }

    public function __invoke(int|float|string|null $number, int $precision = 1): string
    {
        return $this->format->formatCompact($number, $precision);
    }
}