<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Category;
use App\Entity\Technology;
use NeoPHP\Package\Orm\Contract\AbstractRepository;

class TechnologyRepository extends AbstractRepository
{
    protected string $entityClass = Technology::class;

    public function findUsedInCategory(Category $category): array
    {
        return $this->createQueryBuilder('t')
            ->innerJoin('t.projects', 'p')
            ->where('p.category = :category')
            ->andWhere('p.publishedAt IS NOT NULL')
            ->groupBy('t.id')
            ->orderBy('t.name', 'ASC')
            ->setParameter('category', $category->getId())
            ->getResult();
    }
}
