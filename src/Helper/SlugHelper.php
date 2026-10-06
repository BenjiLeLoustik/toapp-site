<?php

declare(strict_types=1);

namespace App\Helper;

class SlugHelper
{
    public static function slugify(string $value, int $maxLength = 150): string
    {
        if (function_exists('iconv')) {
            $value = (string) iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $value);
        }

        $value = strtolower((string) preg_replace('/[^a-zA-Z0-9]+/', '-', $value));

        return substr(trim($value, '-'), 0, $maxLength);
    }
}