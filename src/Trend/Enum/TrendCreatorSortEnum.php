<?php

declare(strict_types=1);

namespace App\Trend\Enum;

enum TrendCreatorSortEnum: string
{
    case PROJECTS = 'projects';
    case LIKES = 'likes';
    case VIEWS = 'views';
}