<?php

declare(strict_types=1);

namespace App\User\Enum;

enum UserDateFormatEnum: string
{
    case DMY = 'd/m/Y';
    case MDY = 'm/d/Y';
    case YMD = 'Y-m-d';

    public static function default(): self
    {
        return self::DMY;
    }

    public function label(): string
    {
        return match ($this) {
            self::DMY => 'DD/MM/YYYY',
            self::MDY => 'MM/DD/YYYY',
            self::YMD => 'YYYY-MM-DD',
        };
    }

    public function dateTime(): string
    {
        return match ($this) {
            self::DMY => 'd/m/Y H:i',
            self::MDY => 'm/d/Y h:i A',
            self::YMD => 'Y-m-d H:i',
        };
    }

    public function format(\DateTimeInterface $date, bool $withTime = false): string
    {
        return $date->format($withTime ? $this->dateTime() : $this->value);
    }
}