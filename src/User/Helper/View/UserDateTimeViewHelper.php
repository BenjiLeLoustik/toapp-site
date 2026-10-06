<?php

declare(strict_types=1);

namespace App\User\Helper\View;

use App\User\Helper\UserFormatHelper;
use NeoPHP\Component\View\Contract\ViewFilterInterface;

class UserDateTimeViewHelper implements ViewFilterInterface
{
    public function __construct(private UserFormatHelper $format)
    {
    }

    public function getName(): string
    {
        return 'user_datetime';
    }

    public function __invoke(mixed $date): string
    {
        return $this->format->formatDate($date, true);
    }
}