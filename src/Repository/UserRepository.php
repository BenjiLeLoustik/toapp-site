<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\User;
use NeoPHP\Package\Orm\Contract\AbstractRepository;

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
}
