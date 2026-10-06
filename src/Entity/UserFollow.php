<?php

declare(strict_types=1);

namespace App\Entity;

use App\Entity\Contract\AbstractEntity;
use App\Repository\UserFollowRepository;
use NeoPHP\Package\Orm\Mapping as ORM;

#[ORM\Entity(repository: UserFollowRepository::class)]
#[ORM\Index(columns: ['follower_id', 'followed_id'], unique: true)]
class UserFollow extends AbstractEntity
{
    #[ORM\ManyToOne(target: User::class, nullable: false, onDelete: 'CASCADE')]
    private ?User $follower = null;

    #[ORM\ManyToOne(target: User::class, nullable: false, onDelete: 'CASCADE')]
    private ?User $followed = null;

    public function getFollower(): ?User
    {
        return $this->follower;
    }

    public function setFollower(User $follower): self
    {
        $this->follower = $follower;
        return $this;
    }

    public function getFollowed(): ?User
    {
        return $this->followed;
    }

    public function setFollowed(User $followed): self
    {
        $this->followed = $followed;
        return $this;
    }
}