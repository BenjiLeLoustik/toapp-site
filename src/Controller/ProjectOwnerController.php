<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Project;
use App\Entity\User;
use App\Project\Enum\ProjectStatusEnum;
use App\Project\Helper\ProjectOwnerHelper;
use NeoPHP\Component\Api\Attribute\MapPagination;
use NeoPHP\Component\Api\Pagination\PageRequest;
use NeoPHP\Component\Controller\Contract\AbstractController;
use NeoPHP\Component\Http\Request\Request;
use NeoPHP\Component\Http\Response\Response;
use NeoPHP\Component\Routing\Attribute\Route;
use NeoPHP\Package\Orm\Contract\EntityManagerInterface;

#[Route('/projects', name: 'project_')]
class ProjectOwnerController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private ProjectOwnerHelper $ownerHelper,
    ) {
    }

    #[Route('/mine', name: 'mine', methods: ['GET'])]
    public function mine(
        Request $request,
        #[MapPagination(defaultLimit: 6, maxLimit: 48)]
        PageRequest $pageRequest,
    ): Response {
        $user = $this->currentUser();
        $status = ProjectStatusEnum::tryFrom((string) $request->query->get('status', '')) ?? ProjectStatusEnum::ALL;
        $search = trim((string) $request->query->get('search', ''));

        return $this->render('pages/project/mine.html.twig', [
            'stats' => $this->ownerHelper->getStats($user),
            'status' => $status,
            'statuses' => ProjectStatusEnum::cases(),
            'search' => $search,
            'projects' => $this->paginate(
                $this->entityManager->getRepository(Project::class)->createOwnerQueryBuilder($user, $status, $search),
                $pageRequest
            ),
        ]);
    }

    #[Route('/{id}/{slug}/delete', name: 'delete', methods: ['GET', 'POST'])]
    public function delete(int $id, string $slug, Request $request): Response
    {
        $user = $this->currentUser();
        $project = $this->ownerHelper->findOwned($user, $id, $slug);

        if ($project === null) {
            return $this->redirectToRoute('project_mine');
        }

        $csrf = 'project_delete_' . $project->getId();
        $errors = [];

        if ($request->isMethod('POST')) {
            $token = $request->request->get('_token');

            $errors = $this->isCsrfTokenValid($csrf, is_string($token) ? $token : null)
                ? $this->ownerHelper->validateDeletion($project, $request->request->all())
                : ['_form' => $this->translate('settings.errors.csrf')];

            if ($errors === []) {
                $name = (string) $project->getName();
                $this->ownerHelper->delete($project);
                $this->addFlash('success', $this->translate('project.delete.flash', ['name' => $name]));

                return $this->redirectToRoute('project_mine');
            }
        }

        return $this->render('pages/project/delete.html.twig', [
            'project' => $project,
            'errors' => $errors,
            'danger' => [
                'action' => 'project_delete',
                'params' => ['id' => $project->getId(), 'slug' => $project->getSlug()],
                'cancel' => 'project_mine',
                'csrf' => $csrf,
                'phrase' => $project->getName(),
                'submit' => $this->translate('project.delete.submit'),
            ],
        ]);
    }

    private function currentUser(): User
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        $user = $this->getUser();

        if (!$user instanceof User) {
            throw $this->createAccessDeniedException();
        }

        return $user;
    }
}