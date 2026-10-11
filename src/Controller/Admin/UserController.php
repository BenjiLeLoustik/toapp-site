<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Admin\Contract\AbstractToolController;
use App\Admin\Helper\UserAdminHelper;
use App\Entity\User;
use NeoPHP\Component\Form\Type\TextType;
use NeoPHP\Component\Http\Response\Response;
use NeoPHP\Component\Routing\Attribute\Route;
use NeoPHP\NeoAdmin\Page\Action;
use NeoPHP\NeoAdmin\Page\Column;

class UserController extends AbstractToolController
{
    public const LIMIT = 50;

    public const ACTIONS = [
        'certify' => 'certified',
        'uncertify' => 'uncertified',
        'deactivate' => 'deactivated',
        'reactivate' => 'reactivated',
        'anonymize' => 'anonymized',
    ];

    #[Route('/admin/users', name: 'admin_user', methods: ['GET', 'POST'])]
    public function index(): Response
    {
        $this->denyUnlessAdmin();
        $term = $this->request()->query->getString('q');
        $form = $this->createAdminForm('user_search', ['q' => $term], [
            'q' => [TextType::class, ['label' => 'Search', 'required' => false, 'help' => 'Email, username, first name or last name.']],
        ]);

        if (($data = $this->submitted($form)) !== null) {
            return $this->redirectToRoute('admin_user', ['q' => $this->text($data['q'] ?? null)]);
        }

        $show = fn (array $row): string => $this->generateUrl('admin_user_show', ['id' => $row['id']]);

        return $this->page('Users')
            ->description($term !== '' ? sprintf('Users matching "%s".', $term) : sprintf('The first %d users.', self::LIMIT))
            ->form($form, 'Search', $term !== '' ? $this->generateUrl('admin_user') : null)
            ->table(null, $this->get(UserAdminHelper::class)->search($term, self::LIMIT), [
                Column::text('username', 'Username')->url($show),
                Column::text('name', 'Name'),
                Column::text('email', 'Email'),
                Column::text('roles', 'Roles'),
                Column::badge('certified', 'Certified', ['yes' => 'success', 'no' => 'info']),
                Column::badge('status', 'Status', ['active' => 'success', 'deactivated' => 'warning', 'deleted' => 'danger']),
                Column::number('projects', 'Projects'),
                Column::text('last_login', 'Last login'),
            ], 'users', 'No user found.', static fn (array $row): array => [
                Action::link('Show', $show($row), 'eye'),
            ])
            ->render($this->status($form));
    }

    #[Route('/admin/users/{id}', name: 'admin_user_show', methods: ['GET', 'POST'], requirements: ['id' => '\d+'])]
    public function show(string $id): Response
    {
        $this->denyUnlessAdmin();
        $users = $this->get(UserAdminHelper::class);
        $user = $this->findOr404(static fn (): User => $users->find($id));
        $form = $this->createAdminForm('user_role', ['role' => ''], [
            'role' => [TextType::class, ['label' => 'Role', 'help' => 'ROLE_ is optional: admin = ROLE_ADMIN.']],
        ]);

        if (($data = $this->submitted($form)) !== null && $this->attempt(
                static fn (): mixed => $users->addRole($user, (string) $data['role']),
                'Role added.',
            )) {
            return $this->redirectToRoute('admin_user_show', ['id' => $id]);
        }

        $token = 'user_' . $id;
        $url = fn (string $action, array $parameters = []): string => $this->generateUrl('admin_user_action', ['id' => $id, 'action' => $action] + $parameters);
        $summary = $users->summary($user);
        $roles = [];

        foreach (array_diff($user->getRoles(), UserAdminHelper::PROTECTED_ROLES) as $role) {
            $roles[] = ['role' => $role];
        }

        $page = $this->page((string) $user->getUsername())
            ->description((string) $user->getEmail())
            ->action(Action::link('Back', $this->generateUrl('admin_user'), 'chevron-right'))
            ->action(Action::link('Projects', $this->generateUrl('admin_project', ['user' => $id]), 'box'))
            ->action($user->isCertified()
                ? Action::post('Uncertify', $url('uncertify'), $token, null, 'shield')
                : Action::post('Certify', $url('certify'), $token, null, 'shield'));

        if (!$user->isDeleted()) {
            $page->action($user->isDeactivated()
                ? Action::post('Reactivate', $url('reactivate'), $token, null, 'user')
                : Action::post('Deactivate', $url('deactivate'), $token, 'Deactivate this account? It will be hidden until it is reactivated.', 'user')->danger());
            $page->action(Action::post('Anonymize', $url('anonymize'), $token, 'Delete the personal data of this account? This cannot be undone.', 'trash')->danger());
        }

        return $page
            ->add($this->details($summary, 'Account', 'user'))
            ->table('Roles', $roles, [Column::code('role', 'Role')], 'shield', 'Only ROLE_USER, given to every user.', static fn (array $row): array => [
                Action::post('Remove', $url('role-remove', ['role' => $row['role']]), $token, sprintf('Remove the role %s?', $row['role']), 'x')->danger(),
            ])
            ->form($form, 'Add the role', null, 'Add a role')
            ->render($this->status($form));
    }

    #[Route('/admin/users/{id}/{action}', name: 'admin_user_action', methods: ['POST'], requirements: ['id' => '\d+', 'action' => 'certify|uncertify|deactivate|reactivate|anonymize|role-remove'])]
    public function action(string $id, string $action): Response
    {
        $this->denyUnlessAdmin();
        $users = $this->get(UserAdminHelper::class);
        $user = $this->findOr404(static fn (): User => $users->find($id));
        $role = $this->request()->query->getString('role');

        $this->handlePost(
            'user_' . $id,
            static fn (): mixed => match ($action) {
                'certify' => $users->certify($user, true),
                'uncertify' => $users->certify($user, false),
                'deactivate' => $users->deactivate($user),
                'reactivate' => $users->reactivate($user),
                'anonymize' => $users->anonymize($user),
                'role-remove' => $users->removeRole($user, $role),
            },
            $action === 'role-remove' ? sprintf('Role %s removed.', $role) : sprintf('Account %s.', self::ACTIONS[$action]),
        );

        return $this->redirectToRoute('admin_user_show', ['id' => $id]);
    }
}