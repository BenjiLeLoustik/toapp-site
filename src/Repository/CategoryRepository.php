<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Category;
use App\Entity\CategoryTranslated;
use NeoPHP\Package\Orm\Contract\AbstractRepository;

class CategoryRepository extends AbstractRepository
{
    protected string $entityClass = Category::class;

    public function findAllTranslated(string $locale): array
    {
        return $this->createQueryBuilder('c')
            ->join('c.translations', 't')
            ->where('t.locale = :locale')
            ->setParameter('locale', $locale)
            ->orderBy('t.name', 'ASC')
            ->getResult();
    }
}
