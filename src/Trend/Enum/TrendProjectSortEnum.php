<?php

declare(strict_types=1);

namespace App\Trend\Enum;

enum TrendProjectSortEnum: string
{
    case VIEWS = 'views';
    case LIKES = 'likes';
    case SHARES = 'shares';
    case RECENT = 'recent';
}