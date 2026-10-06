<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Category;
use App\Entity\Technology;
use NeoPHP\Package\Orm\Contract\AbstractRepository;

class TechnologyRepository extends AbstractRepository
{
    protected string $entityClass = Technology::class;

    public function findUsedInCategory(?Category $category = null): array
    {
        $queryBuilder = $this->createQueryBuilder('t')
            ->innerJoin('t.projects', 'p')
            ->where('p.publishedAt IS NOT NULL')
            ->groupBy('t.id')
            ->orderBy('t.name', 'ASC');

        if ($category !== null) {
            $queryBuilder
                ->andWhere('p.category = :category')
                ->setParameter('category', $category->getId());
        }

        return $queryBuilder->getResult();
    }

    public function findBySlugs(array $slugs): array
    {
        if ($slugs === []) {
            return [];
        }

        return $this->createQueryBuilder('t')
            ->where('t.slug IN (:slugs)')
            ->setParameter('slugs', array_values($slugs))
            ->orderBy('t.name', 'ASC')
            ->getResult();
    }
}