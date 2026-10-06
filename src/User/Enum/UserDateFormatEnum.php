<?php

declare(strict_types=1);

namespace App\User\Enum;

enum UserDateFormatEnum: string
{
    case DMY = 'd/m/Y';
    case MDY = 'm/d/Y';
    case YMD = 'Y-m-d';

    public function label(): string
    {
        return match ($this) {
            self::DMY => 'DD/MM/YYYY',
            self::MDY => 'MM/DD/YYYY',
            self::YMD => 'YYYY-MM-DD',
        };
    }
}