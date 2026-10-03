<?php

namespace App\Project\Helper;

use App\Entity\Project;
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

}