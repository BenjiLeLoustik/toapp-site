<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Admin\Contract\AbstractToolController;
use App\Admin\Exception\AdminException;
use App\Admin\Helper\ProjectAdminHelper;
use App\Admin\Helper\UserAdminHelper;
use App\Entity\Project;
use App\Entity\User;
use App\Project\Enum\ProjectStatusEnum;
use NeoPHP\Component\Http\Response\Response;
use NeoPHP\Component\Routing\Attribute\Route;
use NeoPHP\NeoAdmin\Page\Action;
use NeoPHP\NeoAdmin\Page\Column;

class ProjectController extends AbstractToolController
{
    public const LIMIT = 100;

    #[Route('/admin/projects', name: 'admin_project', methods: ['GET'])]
    public function index(): Response
    {
        $this->denyUnlessAdmin();
        $request = $this->request();
        $status = ProjectStatusEnum::tryFrom($request->query->getString('status')) ?? ProjectStatusEnum::ALL;
        $identifier = $this->text($request->query->get('user'));
        $user = null;

        if ($identifier !== null) {
            try {
                $user = $this->get(UserAdminHelper::class)->find($identifier);
            } catch (AdminException $exception) {
                $this->addFlash('error', $exception->getMessage());

                return $this->redirectToRoute('admin_project', ['status' => $status->value]);
            }
        }

        $statuses = [];

        foreach (ProjectStatusEnum::cases() as $case) {
            $statuses[$case->value] = ucfirst($case->value);
        }

        $show = fn (array $row): string => $this->generateUrl('admin_project_show', ['id' => $row['id']]);
        $page = $this->page('Projects')
            ->description($user instanceof User
                ? sprintf('Projects of %s (%s), the most recent first.', $user->getUsername(), $user->getEmail())
                : sprintf('The %d most recent projects.', self::LIMIT));

        if ($user instanceof User) {
            $page->action(Action::link('All the owners', $this->generateUrl('admin_project', ['status' => $status->value]), 'x'));
        }

        return $page
            ->tabs('status', $statuses, $status->value)
            ->table(null, $this->get(ProjectAdminHelper::class)->list($user, $status, self::LIMIT), [
                Column::text('name', 'Name')->url($show),
                Column::text('owner', 'Owner')->url(fn (array $row): ?string => $row['owner'] !== '-' ? $this->generateUrl('admin_project', ['status' => $status->value, 'user' => $row['owner']]) : null),
                Column::badge('status', 'Status'),
                Column::badge('visibility', 'Visibility'),
                Column::number('views', 'Views'),
                Column::number('likes', 'Likes'),
                Column::text('created', 'Created'),
            ], 'box', 'No project found.', static fn (array $row): array => [
                Action::link('Show', $show($row), 'eye'),
            ])
            ->render();
    }

    #[Route('/admin/projects/{id}', name: 'admin_project_show', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function show(string $id): Response
    {
        $this->denyUnlessAdmin();
        $projects = $this->get(ProjectAdminHelper::class);
        $project = $this->findOr404(static fn (): Project => $projects->get((int) $id));
        $url = fn (string $action): string => $this->generateUrl('admin_project_action', ['id' => $id, 'action' => $action]);
        $token = 'project_' . $id;
        $page = $this->page((string) $project->getName())
            ->description(sprintf('Project #%d.', $project->getId()))
            ->action(Action::link('Back', $this->generateUrl('admin_project'), 'chevron-right'));

        if ($project->getUser() !== null) {
            $page->action(Action::link('Owner', $this->generateUrl('admin_user_show', ['id' => $project->getUser()->getId()]), 'user'));
        }

        if (!$project->isArchived()) {
            $page->action(Action::post('Archive', $url('archive'), $token, 'Archive this project? It will be hidden from the public pages.', 'box'));
        }

        if ($project->getStatus() === ProjectStatusEnum::PUBLISHED) {
            $page->action(Action::post('Unpublish', $url('unpublish'), $token, 'Put this project back to draft?', 'eye'));
        }

        return $page
            ->action(Action::post('Delete', $url('delete'), $token, 'Delete this project and its files? This cannot be undone.', 'trash')->danger())
            ->add($this->details($projects->summary($project), 'Details', 'box'))
            ->render();
    }

    #[Route('/admin/projects/{id}/{action}', name: 'admin_project_action', methods: ['POST'], requirements: ['id' => '\d+', 'action' => 'archive|unpublish|delete'])]
    public function action(string $id, string $action): Response
    {
        $this->denyUnlessAdmin();
        $projects = $this->get(ProjectAdminHelper::class);
        $project = $this->findOr404(static fn (): Project => $projects->get((int) $id));
        $label = sprintf('#%d "%s"', $project->getId(), $project->getName());

        $done = $this->handlePost(
            'project_' . $id,
            static fn (): mixed => match ($action) {
                'archive' => $projects->archive($project),
                'unpublish' => $projects->unpublish($project),
                'delete' => $projects->delete($project),
            },
            sprintf('Action "%s" done on the project %s.', $action, $label),
        );

        return $done && $action === 'delete' ? $this->redirectToRoute('admin_project') : $this->redirectToRoute('admin_project_show', ['id' => $id]);
    }
}