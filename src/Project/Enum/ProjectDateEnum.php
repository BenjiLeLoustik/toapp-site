<?php

namespace App\Project\Enum;

enum ProjectDateEnum: string
{
    case ALL = 'all';
    case WEEK = 'week';
    case MONTH = 'month';
    case YEAR = 'year';

    public function getSince(): ?\DateTime
    {
        return match ($this) {
            self::ALL => null,
            self::WEEK => new \DateTime('-7 days'),
            self::MONTH => new \DateTime('-1 month'),
            self::YEAR => new \DateTime('-1 year'),
        };
    }
}