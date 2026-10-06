<?php

declare(strict_types=1);

namespace App\User\Enum;

enum UserNumberFormatEnum: string
{
    case SPACE_COMMA = 'space_comma';
    case COMMA_DOT = 'comma_dot';
    case DOT_COMMA = 'dot_comma';

    public function format(float $number, int $decimals = 2): string
    {
        return match ($this) {
            self::SPACE_COMMA => number_format($number, $decimals, ',', ' '),
            self::COMMA_DOT => number_format($number, $decimals, '.', ','),
            self::DOT_COMMA => number_format($number, $decimals, ',', '.'),
        };
    }

    public function label(): string
    {
        return $this->format(1234.56);
    }
}