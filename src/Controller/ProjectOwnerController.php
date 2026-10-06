<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Project;
use App\Entity\User;
use App\Project\Enum\ProjectStatsPeriodEnum;
use App\Project\Enum\ProjectStatusEnum;
use App\Project\Helper\ProjectFormHelper;
use App\Project\Helper\ProjectOwnerHelper;
use App\Project\Helper\ProjectStatsHelper;
use NeoPHP\Component\Api\Attribute\MapPagination;
use NeoPHP\Component\Api\Pagination\PageRequest;
use NeoPHP\Component\Controller\Contract\AbstractController;
use NeoPHP\Component\Http\Request\Request;
use NeoPHP\Component\Http\Request\UploadedFile;
use NeoPHP\Component\Http\Response\Response;
use NeoPHP\Component\Routing\Attribute\Route;
use NeoPHP\Package\Orm\Contract\EntityManagerInterface;

#[Route('/projects', name: 'project_')]
class ProjectOwnerController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private ProjectOwnerHelper $ownerHelper,
        private ProjectStatsHelper $statsHelper,
        private ProjectFormHelper $formHelper,
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

    #[Route('/new', name: 'new', methods: ['GET', 'POST'])]
    public function new(Request $request): Response
    {
        return $this->handleForm($request, null);
    }

    #[Route('/{id}/{slug}/edit', name: 'edit', methods: ['GET', 'POST'])]
    public function edit(int $id, string $slug, Request $request): Response
    {
        $project = $this->ownerHelper->findOwned($this->currentUser(), $id, $slug);

        if ($project === null) {
            return $this->redirectToRoute('project_mine');
        }

        return $this->handleForm($request, $project);
    }

    #[Route('/{id}/{slug}/stats', name: 'stats', methods: ['GET'])]
    public function stats(int $id, string $slug, Request $request): Response
    {
        $project = $this->ownerHelper->findOwned($this->currentUser(), $id, $slug);

        if ($project === null) {
            return $this->redirectToRoute('project_mine');
        }

        $period = ProjectStatsPeriodEnum::tryFrom((string) $request->query->get('period', '')) ?? ProjectStatsPeriodEnum::MONTH;

        return $this->render('pages/project/stats.html.twig', [
            'project' => $project,
            'period' => $period,
            'periods' => ProjectStatsPeriodEnum::cases(),
            'stats' => $this->statsHelper->build($project, $period, (int) $request->query->get('page', 1)),
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
            $errors = $this->validCsrf($request, $csrf) ?? $this->ownerHelper->validateDeletion($project, $request->request->all());

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

    private function handleForm(Request $request, ?Project $project): Response
    {
        $user = $this->currentUser();
        $values = $this->formHelper->values($project);
        $errors = [];

        if ($request->isMethod('POST')) {
            $data = $request->request->all();
            $values = $this->formHelper->valuesFromRequest($data);
            $cover = $request->files->get('cover');

            $errors = $this->validCsrf($request, 'project_form') ?? [];

            if ($errors === []) {
                $result = $this->formHelper->save(
                    $project,
                    $user,
                    $values,
                    $cover instanceof UploadedFile ? $cover : null,
                    ($data['remove_cover'] ?? '0') === '1',
                    (array) ($request->files->get('screenshots') ?? []),
                    (array) ($data['remove_screenshots'] ?? [])
                );

                $errors = $result['errors'];

                if ($errors === []) {
                    $this->addFlash('success', $this->translate($project === null ? 'project_form.flash.created' : 'project_form.flash.updated', [
                        'name' => $result['project']->getName(),
                    ]));

                    return $this->redirectToRoute('project_mine');
                }
            }
        }

        return $this->render('pages/project/form.html.twig', [
            'project' => $project,
            'values' => $values,
            'errors' => $errors,
            ...$this->formHelper->options(),
        ]);
    }

    private function validCsrf(Request $request, string $id): ?array
    {
        $token = $request->request->get('_token');

        if ($this->isCsrfTokenValid($id, is_string($token) ? $token : null)) {
            return null;
        }

        return ['_form' => $this->translate('settings.errors.csrf')];
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