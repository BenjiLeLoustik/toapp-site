<?php

declare(strict_types=1);

namespace App\User\Enum;

enum UserNumberFormatEnum: string
{
    case SPACE_COMMA = 'space_comma';
    case COMMA_DOT = 'comma_dot';
    case DOT_COMMA = 'dot_comma';

    public static function default(): self
    {
        return self::COMMA_DOT;
    }

    public function decimalSeparator(): string
    {
        return match ($this) {
            self::SPACE_COMMA, self::DOT_COMMA => ',',
            self::COMMA_DOT => '.',
        };
    }

    public function thousandsSeparator(): string
    {
        return match ($this) {
            self::SPACE_COMMA => ' ',
            self::COMMA_DOT => ',',
            self::DOT_COMMA => '.',
        };
    }

    public function format(float $number, int $decimals = 2): string
    {
        return number_format($number, $decimals, $this->decimalSeparator(), $this->thousandsSeparator());
    }

    public function label(): string
    {
        return $this->format(1234.56);
    }
}