<?php

declare(strict_types=1);

namespace App\Project\Enum;

enum ProjectViewSourceEnum: string
{
    case SEARCH = 'search';
    case DIRECT = 'direct';
    case SOCIAL = 'social';
    case INTERNAL = 'internal';
    case OTHER = 'other';

    public const SEARCH_NAMES = ['google', 'bing', 'duckduckgo', 'yahoo', 'ecosia', 'qwant', 'baidu', 'yandex', 'startpage', 'search'];

    public const SOCIAL_NAMES = ['facebook', 'twitter', 'linkedin', 'reddit', 'discord', 'instagram', 'youtube', 'threads', 'mastodon', 'bsky', 'pinterest', 'tiktok'];

    public const SOCIAL_HOSTS = ['t.co', 'x.com', 'lnkd.in', 'fb.com', 'fb.me'];

    public static function fromReferer(?string $referer, string $appHost): self
    {
        $host = strtolower((string) parse_url((string) $referer, PHP_URL_HOST));

        if ($host === '') {
            return self::DIRECT;
        }

        $host = (string) preg_replace('/^www\./', '', $host);
        $appHost = (string) preg_replace('/^www\./', '', strtolower($appHost));

        if ($host === $appHost) {
            return self::INTERNAL;
        }

        $labels = explode('.', $host);

        if (array_intersect($labels, self::SEARCH_NAMES) !== []) {
            return self::SEARCH;
        }

        if (in_array($host, self::SOCIAL_HOSTS, true) || array_intersect($labels, self::SOCIAL_NAMES) !== []) {
            return self::SOCIAL;
        }

        return self::OTHER;
    }
}