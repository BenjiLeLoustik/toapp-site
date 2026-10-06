<?php

declare(strict_types=1);

namespace App\User\Helper\View;

use App\User\Helper\UserFormatHelper;
use NeoPHP\Component\View\Contract\ViewFilterInterface;

class UserDateViewHelper implements ViewFilterInterface
{
    public function __construct(private UserFormatHelper $format)
    {
    }

    public function getName(): string
    {
        return 'user_date';
    }

    public function __invoke(mixed $date): string
    {
        return $this->format->formatDate($date);
    }
}