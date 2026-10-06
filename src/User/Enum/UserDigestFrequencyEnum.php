<?php

declare(strict_types=1);

namespace App\User\Enum;

enum UserDigestFrequencyEnum: string
{
    case NEVER = 'never';
    case DAILY = 'daily';
    case WEEKLY = 'weekly';
    case MONTHLY = 'monthly';

    public const TOLERANCE = '-1 hour';

    public function interval(): ?\DateInterval
    {
        return match ($this) {
            self::NEVER => null,
            self::DAILY => new \DateInterval('P1D'),
            self::WEEKLY => new \DateInterval('P7D'),
            self::MONTHLY => new \DateInterval('P1M'),
        };
    }

    public function isDue(?\DateTimeInterface $sentAt, \DateTimeImmutable $now): bool
    {
        $interval = $this->interval();

        if ($interval === null) {
            return false;
        }

        if ($sentAt === null) {
            return true;
        }

        return \DateTimeImmutable::createFromInterface($sentAt)->add($interval)->modify(self::TOLERANCE) <= $now;
    }

    public function since(?\DateTimeInterface $sentAt, \DateTimeImmutable $now): \DateTimeImmutable
    {
        if ($sentAt !== null) {
            return \DateTimeImmutable::createFromInterface($sentAt);
        }

        return $now->sub($this->interval() ?? new \DateInterval('P7D'));
    }
}