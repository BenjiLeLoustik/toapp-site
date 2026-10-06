<?php

declare(strict_types=1);

namespace App\User\Helper;

use App\Entity\User;
use App\Entity\UserFollow;
use App\Entity\UserPrivacySetting;
use NeoPHP\Package\Orm\Contract\EntityManagerInterface;

class UserProfileHelper
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private UserSettingsHelper $settings,
    ) {
    }

    public function findVisible(string $slug, ?User $viewer): ?User
    {
        $user = $this->entityManager->getRepository(User::class)->findOneBy(['slug' => strtolower($slug)]);

        if (!$user instanceof User || $user->isDeleted() || $user->isDeactivated()) {
            return null;
        }

        if ($this->isOwner($user, $viewer)) {
            return $user;
        }

        return $this->privacy($user)->isProfilePublic() ? $user : null;
    }

    public function isOwner(User $user, ?User $viewer): bool
    {
        return $viewer !== null && $viewer->getId() === $user->getId();
    }

    public function privacy(User $user): UserPrivacySetting
    {
        return $this->settings->getPrivacy($user);
    }

    public function countFollowers(User $user): int
    {
        return $this->entityManager->getRepository(UserFollow::class)->countFollowers($user);
    }

    public function countFollowing(User $user): int
    {
        return $this->entityManager->getRepository(UserFollow::class)->countFollowing($user);
    }

    public function isFollowing(?User $follower, User $followed): bool
    {
        if ($follower === null || $this->isOwner($followed, $follower)) {
            return false;
        }

        return $this->entityManager->getRepository(UserFollow::class)->findOneFollow($follower, $followed) !== null;
    }

    public function toggleFollow(User $follower, User $followed): bool
    {
        $follow = $this->entityManager->getRepository(UserFollow::class)->findOneFollow($follower, $followed);

        if ($follow !== null) {
            $this->entityManager->remove($follow);
            $this->entityManager->flush();

            return false;
        }

        $follow = (new UserFollow())
            ->setFollower($follower)
            ->setFollowed($followed);

        $this->entityManager->persist($follow);
        $this->entityManager->flush();

        return true;
    }
}