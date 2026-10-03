<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Project;
use App\Project\Helper\ProjectHelper;
use NeoPHP\Component\Controller\Contract\AbstractController;
use NeoPHP\Component\Http\Response\Response;
use NeoPHP\Component\Routing\Attribute\Route;

#[Route('/project', name: 'project_')]
class ProjectController extends AbstractController
{
    public function __construct(
        private ProjectHelper $projectHelper,
    ) {
    }

    #[Route('/{id}/{slug}', name: 'show')]
    public function showProject(int $id, string $slug): Response
    {
        $project = $this->projectHelper->validateProject($id, $slug);
        if (!$project) {
            return $this->redirectToRoute('home_index');
        }

        return $this->render('pages/project/show.html.twig', [
            'project' => $project,
        ]);
    }
}
