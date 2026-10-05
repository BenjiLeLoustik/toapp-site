<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Category;
use App\Entity\Project;
use App\Project\Enum\ProjectSortEnum;
use App\Project\Search\ProjectSearchFilters;
use NeoPHP\Package\Orm\Contract\AbstractRepository;
use NeoPHP\Package\Orm\Query\QueryBuilder;

class ProjectRepository extends AbstractRepository
{
    protected string $entityClass = Project::class;

    public function findPopular(int $limit): array
    {
        return $this->findBy([], ['createdAt' => 'ASC'], $limit);
    }

    public function createSearchQueryBuilder(?Category $category, ProjectSearchFilters $filters): QueryBuilder
    {
        $queryBuilder = $this->createQueryBuilder('p')
            ->leftJoin('p.likes', 'l')
            ->leftJoin('p.views', 'v')
            ->where('p.publishedAt IS NOT NULL')
            ->groupBy('p.id');

        if ($category !== null) {
            $queryBuilder
                ->andWhere('p.category = :category')
                ->setParameter('category', $category->getId());
        }

        if ($filters->hasSearch()) {
            $queryBuilder
                ->andWhere('(p.name LIKE :search OR p.shortDescription LIKE :search)')
                ->setParameter('search', '%' . addcslashes($filters->getSearch(), '%_') . '%');
        }

        if ($filters->getTechnologies() !== []) {
            $queryBuilder
                ->innerJoin('p.technologies', 't')
                ->andWhere('t.slug IN (:technologies)')
                ->setParameter('technologies', $filters->getTechnologies());
        }

        $since = $filters->getDate()->getSince();

        if ($since !== null) {
            $queryBuilder
                ->andWhere('p.publishedAt >= :since')
                ->setParameter('since', $since->format('Y-m-d H:i:s'));
        }

        match ($filters->getSort()) {
            ProjectSortEnum::RECENT => $queryBuilder->orderBy('p.publishedAt', 'DESC'),
            ProjectSortEnum::OLDEST => $queryBuilder->orderBy('p.publishedAt', 'ASC'),
            ProjectSortEnum::POPULAR => $queryBuilder->orderBy('COUNT(DISTINCT v.id)', 'DESC')->addOrderBy('p.publishedAt', 'DESC'),
            ProjectSortEnum::RELEVANCE => $queryBuilder->orderBy('COUNT(DISTINCT l.id)', 'DESC')->addOrderBy('p.publishedAt', 'DESC'),
        };

        return $queryBuilder;
    }

    public function createTrendingQueryBuilder(ProjectDateEnum $period): QueryBuilder
    {
        $since = ($period->getSince() ?? new \DateTime('-7 days'))->format('Y-m-d H:i:s');

        return $this->createQueryBuilder('p')
            ->leftJoin('p.likes', 'l', 'l.createdAt >= :since')
            ->leftJoin('p.views', 'v', 'v.createdAt >= :since')
            ->where('p.publishedAt IS NOT NULL')
            ->groupBy('p.id')
            ->having('COUNT(DISTINCT l.id) + COUNT(DISTINCT v.id) > 0')
            ->orderBy('COUNT(DISTINCT l.id) * 3 + COUNT(DISTINCT v.id)', 'DESC')
            ->addOrderBy('p.publishedAt', 'DESC')
            ->setParameter('since', $since);
    }
}
