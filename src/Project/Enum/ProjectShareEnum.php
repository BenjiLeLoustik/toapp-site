<?php

namespace App\Project\Enum;

enum ProjectShareEnum: string
{
    case LINK = 'link';
    case EMAIL = 'email';
    case FACEBOOK = 'facebook';
    case X = 'x';
    case LINKEDIN = 'linkedin';
    case DISCORD = 'discord';

    public function label(): string
    {
        return match ($this) {
            self::LINK => 'link',
            self::EMAIL => 'email',
            self::FACEBOOK => 'facebook',
            self::X => 'x',
            self::LINKEDIN => 'linkedin',
            self::DISCORD => 'discord',
        };
    }
}
