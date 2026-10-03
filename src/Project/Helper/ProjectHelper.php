<?php

namespace App\Project\Helper;

use App\Entity\Project;
use NeoPHP\Component\Http\Response\RedirectResponse;
use NeoPHP\Component\Http\Response\Response;
use NeoPHP\Component\Routing\Contract\RoutingInterface;
use NeoPHP\Package\Orm\Contract\EntityManagerInterface;

class ProjectHelper
{
    public function __construct(
        private RoutingInterface $router,
        private EntityManagerInterface $entityManager
    ) {
    }

    public function validateProject(int $projectId, string $projectSlug): ?Response
    {
        $project = $this->entityManager->getRepository(Project::class)->find($projectId);

        if ($project === null || $project->getSlug() !== $projectSlug) {
            return new RedirectResponse(
                $this->router->generate('home_index'),
            );
        }

        return null;
    }

}