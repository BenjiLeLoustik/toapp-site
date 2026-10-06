<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\User;
use App\Entity\UserFollow;
use NeoPHP\Package\Orm\Contract\AbstractRepository;

class UserFollowRepository extends AbstractRepository
{
    protected string $entityClass = UserFollow::class;

    public function countFollowers(User $user): int
    {
        return $this->count(['followed' => $user]);
    }

    public function countFollowing(User $user): int
    {
        return $this->count(['follower' => $user]);
    }

    public function findOneFollow(User $follower, User $followed): ?UserFollow
    {
        return $this->findOneBy(['follower' => $follower, 'followed' => $followed]);
    }
}