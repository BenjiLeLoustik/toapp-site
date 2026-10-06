<?php

namespace App\Entity\Contract;

use NeoPHP\Package\Orm\Contract\CollectionInterface;

trait TranslatableTrait
{
    abstract public function getTranslations(): CollectionInterface;

    public function getTranslation(string $locale): ?AbstractTranslatedEntity
    {
        foreach ($this->getTranslations() as $translation) {
            if ($translation->getLocale() === $locale) {
                return $translation;
            }
        }

        return null;
    }
}