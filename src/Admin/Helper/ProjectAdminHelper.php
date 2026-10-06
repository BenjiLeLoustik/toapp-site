<?php

declare(strict_types=1);

namespace App\Admin\Helper;

use App\Admin\Exception\AdminException;
use App\Entity\Project;
use App\Entity\User;
use App\Project\Enum\ProjectStatusEnum;
use App\Project\Helper\ProjectOwnerHelper;
use NeoPHP\Package\Orm\Contract\EntityManagerInterface;

class ProjectAdminHelper
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private ProjectOwnerHelper $ownerHelper,
    ) {
    }

    public function list(?User $user, ProjectStatusEnum $status, int $limit = 50): array
    {
        $queryBuilder = $this->entityManager->getRepository(Project::class)->createQueryBuilder('p');

        if ($user !== null) {
            $queryBuilder
                ->where('p.user = :user')
                ->setParameter('user', $user->getId());
        }

        $status->apply($queryBuilder);

        $projects = $queryBuilder
            ->orderBy('p.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getResult();

        return array_map(fn (Project $project): array => $this->summary($project), $projects);
    }

    public function get(int $id): Project
    {
        $project = $this->entityManager->getRepository(Project::class)->find($id);

        if (!$project instanceof Project) {
            throw new AdminException(sprintf('The project #%d does not exist.', $id));
        }

        return $project;
    }

    public function summary(Project $project): array
    {
        return [
            'id' => $project->getId(),
            'name' => $project->getName(),
            'slug' => $project->getSlug(),
            'owner' => $project->getUser()?->getUsername() ?? '-',
            'status' => $project->getStatus()->value,
            'visibility' => $project->getVisibilityEnum()->value,
            'views' => count($project->getViews()),
            'likes' => count($project->getLikes()),
            'created' => $project->getCreatedAt()?->format('Y-m-d H:i'),
        ];
    }

    public function archive(Project $project): void
    {
        if ($project->isArchived()) {
            throw new AdminException('This project is already archived.');
        }

        $project->setArchivedAt(new \DateTime());
        $this->entityManager->flush();
    }

    public function unpublish(Project $project): void
    {
        if ($project->getStatus() !== ProjectStatusEnum::PUBLISHED) {
            throw new AdminException('This project is not published.');
        }

        $project->setPublishedAt(null);
        $this->entityManager->flush();
    }

    public function delete(Project $project): void
    {
        $this->ownerHelper->delete($project);
    }
}