<?php

namespace App\Project\Helper;

use App\Entity\Project;
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

}