<?php

declare(strict_types=1);

namespace App\User\Helper\View;

use App\User\Helper\UserFormatHelper;
use NeoPHP\Component\View\Contract\ViewFilterInterface;

class UserNumberViewHelper implements ViewFilterInterface
{
    public function __construct(private UserFormatHelper $format)
    {
    }

    public function getName(): string
    {
        return 'user_number';
    }

    public function __invoke(mixed $number, int $decimals = 0): string
    {
        return $this->format->formatNumber($number, $decimals);
    }
}