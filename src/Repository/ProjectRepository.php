<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Category;
use App\Entity\Project;
use App\Entity\User;
use App\Project\Enum\ProjectSortEnum;
use App\Project\Enum\ProjectStatusEnum;
use App\Project\Enum\ProjectVisibilityEnum;
use App\Project\Search\ProjectSearchFilters;
use App\Trend\Enum\TrendProjectSortEnum;
use NeoPHP\Package\Orm\Contract\AbstractRepository;
use NeoPHP\Package\Orm\Query\QueryBuilder;

class ProjectRepository extends AbstractRepository
{
    protected string $entityClass = Project::class;

    public function findPopular(int $limit): array
    {
        $queryBuilder = $this->createQueryBuilder('p')
            ->leftJoin('p.likes', 'l')
            ->groupBy('p.id');

        return $this->applyPublicFilter($queryBuilder)
            ->orderBy('COUNT(DISTINCT l.id)', 'DESC')
            ->addOrderBy('p.publishedAt', 'DESC')
            ->setMaxResults($limit)
            ->getResult();
    }

    public function countPublic(): int
    {
        return (int) $this->applyPublicFilter($this->createQueryBuilder('p')->select('COUNT(p.id) AS total'))
            ->getSingleScalarResult();
    }

    public function countPublicByCategory(): array
    {
        $queryBuilder = $this->createQueryBuilder('p')
            ->select('p.category AS category', 'COUNT(p.id) AS total')
            ->where('p.category IS NOT NULL')
            ->groupBy('p.category');

        $counts = [];

        foreach ($this->applyPublicFilter($queryBuilder)->getScalarResult() as $row) {
            $counts[(int) $row['category']] = (int) $row['total'];
        }

        return $counts;
    }

    public function createSearchQueryBuilder(?Category $category, ProjectSearchFilters $filters): QueryBuilder
    {
        $queryBuilder = $this->createQueryBuilder('p')
            ->leftJoin('p.likes', 'l')
            ->leftJoin('p.views', 'v')
            ->groupBy('p.id');

        $this->applyPublicFilter($queryBuilder);

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

    public function createTrendingProjectsQueryBuilder(string $search, TrendProjectSortEnum $sort): QueryBuilder
    {
        $queryBuilder = $this->createQueryBuilder('p')
            ->leftJoin('p.likes', 'l')
            ->leftJoin('p.views', 'v')
            ->leftJoin('p.shares', 's')
            ->groupBy('p.id');

        $this->applyPublicFilter($queryBuilder);

        if ($search !== '') {
            $queryBuilder
                ->andWhere('(p.name LIKE :search OR p.shortDescription LIKE :search)')
                ->setParameter('search', '%' . addcslashes($search, '%_') . '%');
        }

        match ($sort) {
            TrendProjectSortEnum::VIEWS => $queryBuilder->orderBy('COUNT(DISTINCT v.id)', 'DESC'),
            TrendProjectSortEnum::LIKES => $queryBuilder->orderBy('COUNT(DISTINCT l.id)', 'DESC'),
            TrendProjectSortEnum::SHARES => $queryBuilder->orderBy('COUNT(DISTINCT s.id)', 'DESC'),
            TrendProjectSortEnum::RECENT => $queryBuilder->orderBy('p.publishedAt', 'DESC'),
        };

        return $queryBuilder->addOrderBy('p.id', 'DESC');
    }

    public function createOwnerQueryBuilder(User $user, ProjectStatusEnum $status, string $search): QueryBuilder
    {
        $queryBuilder = $this->createQueryBuilder('p')
            ->where('p.user = :user')
            ->setParameter('user', $user->getId());

        $status->apply($queryBuilder);

        if ($search !== '') {
            $queryBuilder
                ->andWhere('(p.name LIKE :search OR p.shortDescription LIKE :search)')
                ->setParameter('search', '%' . addcslashes($search, '%_') . '%');
        }

        return $queryBuilder
            ->orderBy('p.createdAt', 'DESC')
            ->addOrderBy('p.id', 'DESC');
    }

    public function createProfileQueryBuilder(User $user, bool $isOwner): QueryBuilder
    {
        $queryBuilder = $this->createQueryBuilder('p')
            ->where('p.user = :profileUser')
            ->setParameter('profileUser', $user->getId());

        if (!$isOwner) {
            $this->applyPublicFilter($queryBuilder);
        }

        return $queryBuilder
            ->orderBy('p.createdAt', 'DESC')
            ->addOrderBy('p.id', 'DESC');
    }

    public function createFavoritesQueryBuilder(User $user): QueryBuilder
    {
        $queryBuilder = $this->createQueryBuilder('p')
            ->innerJoin('p.favorites', 'f')
            ->where('f.user = :favoriteUser')
            ->setParameter('favoriteUser', $user->getId());

        return $this->applyPublicFilter($queryBuilder)
            ->orderBy('f.createdAt', 'DESC')
            ->addOrderBy('p.id', 'DESC');
    }

    public function applyPublicFilter(QueryBuilder $queryBuilder, string $alias = 'p'): QueryBuilder
    {
        return ProjectStatusEnum::PUBLISHED
            ->apply($queryBuilder, $alias)
            ->andWhere($alias . '.visibility = :publicVisibility')
            ->setParameter('publicVisibility', ProjectVisibilityEnum::PUBLIC->value);
    }
}