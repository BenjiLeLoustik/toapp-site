<?php

namespace App\Repository\Contract;

use NeoPHP\Package\Orm\Contract\AbstractRepository;

abstract class AbstractTranslatedRepository extends AbstractRepository
{
    public function findAllTranslated(string $locale): array
    {
        return $this->findBy(
            ['locale' => $locale],
            ['name' => 'ASC']
        );
    }

    public function findOneByLocale(string $locale): ?object
    {
        return $this->findOneBy([
            'locale' => $locale,
        ]);
    }
}