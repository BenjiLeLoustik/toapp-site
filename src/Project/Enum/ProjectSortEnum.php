<?php

namespace App\Project\Enum;

enum ProjectSortEnum: string
{
    case RELEVANCE = 'relevance';
    case RECENT = 'recent';
    case OLDEST = 'oldest';
    case POPULAR = 'popular';
}
