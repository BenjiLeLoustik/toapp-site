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
            throw new AdminException('The locale "{locale}" is not valid (e.g. en, fr).', 0, null, ['locale' => $locale]);
        }

        return $locale;
    }

    private function icon(string $icon): string
    {
        $icon = strtolower(trim($icon));

        if (preg_match('/^[a-z0-9-]+$/', $icon) !== 1) {
            throw new AdminException('The icon "{icon}" is not a valid Lucide icon name (e.g. mail, globe).', 0, null, ['icon' => $icon]);
        }

        return $icon;
    }

    private function notEmpty(string $value, string $label): string
    {
        $value = trim($value);

        if ($value === '') {
            throw new AdminException('The {label} cannot be empty.', 0, null, ['label' => $label]);
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