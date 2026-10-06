<?php

declare(strict_types=1);

namespace App\Admin\Helper;

use App\Admin\Exception\AdminException;
use App\Entity\User;
use App\User\Helper\UserSettingsHelper;
use NeoPHP\Package\Orm\Contract\EntityManagerInterface;

class UserAdminHelper
{
    public const PROTECTED_ROLES = ['ROLE_USER'];

    public function __construct(
        private EntityManagerInterface $entityManager,
        private UserSettingsHelper $settings,
    ) {
    }

    public function search(string $term, int $limit = 20): array
    {
        $term = trim($term);

        $queryBuilder = $this->entityManager->getRepository(User::class)->createQueryBuilder('u')
            ->orderBy('u.id', 'ASC')
            ->setMaxResults($limit);

        if ($term !== '') {
            $queryBuilder
                ->where('(u.email LIKE :term OR u.username LIKE :term OR u.firstname LIKE :term OR u.lastname LIKE :term)')
                ->setParameter('term', '%' . addcslashes(ltrim($term, '@'), '%_') . '%');
        }

        return array_map(fn (User $user): array => $this->summary($user), $queryBuilder->getResult());
    }

    public function find(string $identifier): User
    {
        $identifier = trim($identifier);
        $repository = $this->entityManager->getRepository(User::class);

        $user = ctype_digit($identifier)
            ? $repository->find((int) $identifier)
            : ($repository->findOneBy(['email' => strtolower($identifier)])
                ?? $repository->findOneBy(['username' => '@' . ltrim($identifier, '@')])
                ?? $repository->findOneBy(['username' => ltrim($identifier, '@')])
                ?? $repository->findOneBy(['slug' => strtolower(ltrim($identifier, '@'))]));

        if (!$user instanceof User) {
            throw new AdminException(sprintf('No user found for "%s" (id, email, username or slug).', $identifier));
        }

        return $user;
    }

    public function summary(User $user): array
    {
        return [
            'id' => $user->getId(),
            'name' => trim($user->getFirstname() . ' ' . $user->getLastname()),
            'username' => $user->getUsername(),
            'email' => $user->getEmail(),
            'roles' => implode(', ', $user->getRoles()),
            'certified' => $user->isCertified() ? 'yes' : 'no',
            'status' => match (true) {
                $user->isDeleted() => 'deleted',
                $user->isDeactivated() => 'deactivated',
                default => 'active',
            },
            'projects' => count($user->getProjects()),
            'created' => $user->getCreatedAt()?->format('Y-m-d H:i'),
            'last_login' => $user->getLastLoginAt()?->format('Y-m-d H:i') ?? '-',
        ];
    }

    public function addRole(User $user, string $role): void
    {
        $role = $this->role($role);
        $roles = array_values(array_unique([...$this->customRoles($user), $role]));

        $user->setRoles($roles);
        $this->entityManager->flush();
    }

    public function removeRole(User $user, string $role): void
    {
        $role = $this->role($role);

        if (in_array($role, self::PROTECTED_ROLES, true)) {
            throw new AdminException(sprintf('The role "%s" is given to every user and cannot be removed.', $role));
        }

        $user->setRoles(array_values(array_diff($this->customRoles($user), [$role])));
        $this->entityManager->flush();
    }

    public function certify(User $user, bool $certified): void
    {
        $user->setCertified($certified);
        $this->entityManager->flush();
    }

    public function deactivate(User $user): void
    {
        $this->assertNotDeleted($user);
        $this->settings->deactivate($user);
    }

    public function reactivate(User $user): void
    {
        $this->assertNotDeleted($user);
        $user->setDeactivatedAt(null);
        $this->entityManager->flush();
    }

    public function anonymize(User $user): void
    {
        $this->assertNotDeleted($user);
        $this->settings->anonymize($user);
    }

    private function customRoles(User $user): array
    {
        return array_values(array_diff($user->getRoles(), self::PROTECTED_ROLES));
    }

    private function role(string $role): string
    {
        $role = strtoupper(trim($role));
        $role = str_starts_with($role, 'ROLE_') ? $role : 'ROLE_' . $role;

        if (preg_match('/^ROLE_[A-Z0-9_]+$/', $role) !== 1) {
            throw new AdminException(sprintf('The role "%s" is not valid (e.g. ROLE_ADMIN).', $role));
        }

        return $role;
    }

    private function assertNotDeleted(User $user): void
    {
        if ($user->isDeleted()) {
            throw new AdminException('This account has already been deleted.');
        }
    }
}