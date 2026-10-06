<?php

declare(strict_types=1);

namespace App\Website\Enum;

enum WebsiteContactFormatEnum: string
{
    case EMAIL = 'email';
    case PHONE = 'phone';
    case LINK = 'link';
    case TEXT = 'text';

    public function getHref(string $value): ?string
    {
        return match ($this) {
            self::EMAIL => 'mailto:' . trim($value),
            self::PHONE => 'tel:' . preg_replace('/[^0-9+]/', '', $value),
            self::LINK => preg_match('#^https?://#i', $value) === 1 ? $value : 'https://' . ltrim($value, '/'),
            self::TEXT => null,
        };
    }

    public function isExternal(): bool
    {
        return $this === self::LINK;
    }

    public function label(): string
    {
        return match ($this) {
            self::EMAIL => 'Email',
            self::PHONE => 'Phone',
            self::LINK => 'Link',
            self::TEXT => 'Text',
        };
    }
}