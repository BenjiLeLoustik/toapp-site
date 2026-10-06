<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\User;
use App\Trend\Enum\TrendCreatorSortEnum;
use NeoPHP\Package\Orm\Contract\AbstractRepository;
use NeoPHP\Package\Orm\Query\QueryBuilder;

class UserRepository extends AbstractRepository
{
    protected string $entityClass = User::class;

    public function findTopContributors(int $limit = 8): array
    {
        return $this->createQueryBuilder('u')
            ->innerJoin('u.projects', 'p')
            ->where('p.publishedAt IS NOT NULL')
            ->groupBy('u.id')
            ->orderBy('COUNT(p.id)', 'DESC')
            ->addOrderBy('u.firstname', 'ASC')
            ->setMaxResults($limit)
            ->getResult();
    }

    public function createTopCreatorsQueryBuilder(string $search, TrendCreatorSortEnum $sort): QueryBuilder
    {
        $queryBuilder = $this->createQueryBuilder('u')
            ->innerJoin('u.projects', 'p')
            ->leftJoin('p.likes', 'l')
            ->leftJoin('p.views', 'v')
            ->where('p.publishedAt IS NOT NULL')
            ->groupBy('u.id');

        if ($search !== '') {
            $queryBuilder
                ->andWhere('(u.firstname LIKE :search OR u.lastname LIKE :search OR u.username LIKE :search)')
                ->setParameter('search', '%' . addcslashes($search, '%_') . '%');
        }

        match ($sort) {
            TrendCreatorSortEnum::PROJECTS => $queryBuilder->orderBy('COUNT(DISTINCT p.id)', 'DESC'),
            TrendCreatorSortEnum::LIKES => $queryBuilder->orderBy('COUNT(DISTINCT l.id)', 'DESC'),
            TrendCreatorSortEnum::VIEWS => $queryBuilder->orderBy('COUNT(DISTINCT v.id)', 'DESC'),
        };

        return $queryBuilder->addOrderBy('u.id', 'ASC');
    }
}
