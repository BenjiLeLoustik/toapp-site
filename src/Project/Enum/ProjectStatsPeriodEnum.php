<?php

declare(strict_types=1);

namespace App\Project\Enum;

enum ProjectStatsPeriodEnum: string
{
    case WEEK = '7d';
    case MONTH = '30d';
    case QUARTER = '90d';
    case YEAR = '12m';
    case ALL = 'all';

    public function days(): ?int
    {
        return match ($this) {
            self::WEEK => 7,
            self::MONTH => 30,
            self::QUARTER => 90,
            self::YEAR => 365,
            self::ALL => null,
        };
    }

    public function since(\DateTimeImmutable $now): ?\DateTimeImmutable
    {
        $days = $this->days();

        return $days === null ? null : $now->modify('-' . $days . ' days');
    }

    public function previousSince(\DateTimeImmutable $now): ?\DateTimeImmutable
    {
        $days = $this->days();

        return $days === null ? null : $now->modify('-' . ($days * 2) . ' days');
    }

    public function bucket(): string
    {
        return match ($this) {
            self::WEEK, self::MONTH => 'day',
            self::QUARTER => 'week',
            self::YEAR, self::ALL => 'month',
        };
    }
}