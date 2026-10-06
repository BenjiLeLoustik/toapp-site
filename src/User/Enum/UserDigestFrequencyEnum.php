<?php

declare(strict_types=1);

namespace App\User\Enum;

enum UserDigestFrequencyEnum: string
{
    case NEVER = 'never';
    case DAILY = 'daily';
    case WEEKLY = 'weekly';
    case MONTHLY = 'monthly';
}