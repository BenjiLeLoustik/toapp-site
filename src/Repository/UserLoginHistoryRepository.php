<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\User;
use App\Entity\UserLoginHistory;
use NeoPHP\Package\Orm\Contract\AbstractRepository;

class UserLoginHistoryRepository extends AbstractRepository
{
    protected string $entityClass = UserLoginHistory::class;

    public function findRecentForUser(User $user, int $limit = 10): array
    {
        return $this->createQueryBuilder('h')
            ->where('h.user = :user')
            ->orderBy('h.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->setParameter('user', $user->getId())
            ->getResult();
    }

    public function findPreviousForUser(User $user): ?UserLoginHistory
    {
        $history = $this->findRecentForUser($user, 2);

        return $history[1] ?? null;
    }
}