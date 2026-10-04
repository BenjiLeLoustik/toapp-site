<?php

namespace App\Project\Helper;

use App\Entity\Project;
use App\Entity\ProjectLike;
use App\Entity\ProjectShare;
use App\Entity\ProjectView;
use App\Entity\User;
use App\Project\Enum\ProjectShareEnum;
use NeoPHP\Package\Orm\Contract\EntityManagerInterface;

class ProjectHelper
{
    public function __construct(
        private EntityManagerInterface $entityManager
    ) {
    }

    public function validateProject(int $projectId, string $projectSlug): ?Project
    {
        /** @var null|Project $project */
        $project = $this->entityManager->getRepository(Project::class)->find($projectId);

        if ($project === null || $project->getSlug() !== $projectSlug) {
            return null;
        }

        return $project;
    }

    public function addView(Project $project, ?User $user, ?string $ip): void
    {
        $projectViewRepository = $this->entityManager->getRepository(ProjectView::class);

        if ($user !== null && $projectViewRepository->existsForUser($project, $user)) {
            return;
        }

        $view = new ProjectView();
        $view->setProject($project);
        $view->setUser($user);
        $view->setIp($ip);

        $this->entityManager->persist($view);
        $this->entityManager->flush();
    }

    public function addShare(Project $project, ProjectShareEnum $type, ?User $user, ?string $ip): void
    {
        $share = new ProjectShare();
        $share->setProject($project);
        $share->setType($type);
        $share->setUser($user);
        $share->setIp($ip);

        $this->entityManager->persist($share);
        $this->entityManager->flush();
    }

    public function isLiked(Project $project, ?User $user): bool
    {
        if ($user === null) {
            return false;
        }

        return $this->entityManager->getRepository(ProjectLike::class)->findOneBy([
            'project' => $project,
            'user' => $user,
        ]) !== null;
    }

    public function toggleLike(Project $project, User $user): bool
    {
        $like = $this->entityManager->getRepository(ProjectLike::class)->findOneBy([
            'project' => $project,
            'user' => $user,
        ]);

        if ($like !== null) {
            $this->entityManager->remove($like);
            $this->entityManager->flush();

            return false;
        }

        $like = new ProjectLike();
        $like->setProject($project);
        $like->setUser($user);

        $this->entityManager->persist($like);
        $this->entityManager->flush();

        return true;
    }

    public function countLikes(Project $project): int
    {
        return $this->entityManager->getRepository(ProjectLike::class)->count(['project' => $project]);
    }

}