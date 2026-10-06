<?php

declare(strict_types=1);

namespace App\Admin\Helper;

use App\Admin\Exception\AdminException;
use App\Entity\Contract\AbstractTranslatedEntity;

trait AdminValidationTrait
{
    private function locale(string $locale): string
    {
        $locale = strtolower(trim($locale));

        if (preg_match('/^[a-z]{2}(_[a-z]{2})?$/', $locale) !== 1) {
            throw new AdminException(sprintf('The locale "%s" is not valid (e.g. en, fr).', $locale));
        }

        return $locale;
    }

    private function icon(string $icon): string
    {
        $icon = strtolower(trim($icon));

        if (preg_match('/^[a-z0-9-]+$/', $icon) !== 1) {
            throw new AdminException(sprintf('The icon "%s" is not a valid Lucide icon name (e.g. mail, phone).', $icon));
        }

        return $icon;
    }

    private function notEmpty(string $value, string $label): string
    {
        $value = trim($value);

        if ($value === '') {
            throw new AdminException(sprintf('The %s cannot be empty.', $label));
        }

        return $value;
    }

    private function findTranslation(iterable $translations, string $locale): ?AbstractTranslatedEntity
    {
        foreach ($translations as $translation) {
            if ($translation->getLocale() === $locale) {
                return $translation;
            }
        }

        return null;
    }

    private function locales(iterable $translations): string
    {
        $locales = [];

        foreach ($translations as $translation) {
            $locales[] = (string) $translation->getLocale();
        }

        return $locales !== [] ? implode(', ', $locales) : '-';
    }
}