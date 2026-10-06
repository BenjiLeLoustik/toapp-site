<?php

namespace App\Project\Enum;

enum ProjectShareEnum: string
{
    case LINK = 'link';
    case EMAIL = 'email';
    case FACEBOOK = 'facebook';
    case X_TWITTER = 'x_twitter';
    case LINKEDIN = 'linkedin';
    case DISCORD = 'discord';

    public function label(): string
    {
        return match ($this) {
            self::LINK => 'link',
            self::EMAIL => 'email',
            self::FACEBOOK => 'facebook',
            self::X_TWITTER => 'x_twitter',
            self::LINKEDIN => 'linkedin',
            self::DISCORD => 'discord',
        };
    }

    public function getShareLink(string $url): string
    {
        return match ($this) {
            self::LINK, self::DISCORD => $url,
            self::EMAIL => 'mailto:?body=' . urlencode($url),
            self::FACEBOOK => 'https://www.facebook.com/sharer/sharer.php?u=' . urlencode($url),
            self::X_TWITTER => 'https://x.com/intent/post?url=' . urlencode($url),
            self::LINKEDIN => 'https://www.linkedin.com/sharing/share-offsite/?url=' . urlencode($url),
        };
    }
}
