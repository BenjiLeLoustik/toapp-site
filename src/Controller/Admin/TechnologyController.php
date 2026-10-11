<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Admin\Contract\AbstractToolController;
use App\Admin\Helper\TechnologyAdminHelper;
use App\Entity\Technology;
use NeoPHP\Component\Form\Type\ChoiceType;
use NeoPHP\Component\Form\Type\TextType;
use NeoPHP\Component\Http\Response\Response;
use NeoPHP\Component\Routing\Attribute\Route;
use NeoPHP\NeoAdmin\Page\Action;
use NeoPHP\NeoAdmin\Page\Column;

class TechnologyController extends AbstractToolController
{
    #[Route('/admin/technologies', name: 'admin_technology', methods: ['GET', 'POST'])]
    public function index(): Response
    {
        $this->denyUnlessAdmin();
        $technologies = $this->get(TechnologyAdminHelper::class);
        $form = $this->createAdminForm('technology_add', ['name' => '', 'slug' => ''], [
            'name' => [TextType::class, ['label' => 'Name']],
            'slug' => [TextType::class, ['label' => 'Slug', 'required' => false, 'help' => 'Generated from the name when empty.']],
        ]);
        $data = $this->submitted($form);

        if ($data !== null && $this->attempt(
                fn (): Technology => $technologies->add((string) $data['name'], $this->text($data['slug'] ?? null)),
                static fn (Technology $technology): string => sprintf('Technology "%s" added (slug: %s).', $technology->getName(), $technology->getSlug()),
            )) {
            return $this->redirectToRoute('admin_technology');
        }

        $edit = fn (array $row): string => $this->generateUrl('admin_technology_edit', ['slug' => $row['slug']]);

        return $this->page('Technologies')
            ->description('The technologies that can be linked to the projects.')
            ->table(null, $technologies->all(), [
                Column::text('name', 'Name')->url($edit),
                Column::code('slug', 'Slug'),
                Column::number('projects', 'Projects'),
            ], 'tag', 'No technology yet.', fn (array $row): array => [
                Action::link('Edit', $edit($row), 'edit'),
                Action::post(
                    'Delete',
                    $this->generateUrl('admin_technology_delete', ['slug' => $row['slug'], 'detach' => $row['projects'] > 0 ? 1 : null]),
                    'technology_delete_' . $row['slug'],
                    $row['projects'] > 0
                        ? sprintf('Delete "%s" and detach it from its %d project(s)?', $row['name'], $row['projects'])
                        : sprintf('Delete "%s"?', $row['name']),
                    'trash',
                )->danger(),
            ])
            ->form($form, 'Add', null, 'New technology')
            ->render($this->status($form));
    }

    #[Route('/admin/technologies/{slug}', name: 'admin_technology_edit', methods: ['GET', 'POST'])]
    public function edit(string $slug): Response
    {
        $this->denyUnlessAdmin();
        $technologies = $this->get(TechnologyAdminHelper::class);
        $technology = $this->findOr404(static fn (): Technology => $technologies->get($slug));
        $targets = array_values(array_diff($technologies->slugs(), [$slug]));

        $rename = $this->createAdminForm('technology_rename', ['name' => $technology->getName(), 'slug' => $technology->getSlug()], [
            'name' => [TextType::class, ['label' => 'Name']],
            'slug' => [TextType::class, ['label' => 'Slug']],
        ]);
        $merge = $this->createAdminForm('technology_merge', ['target' => null], [
            'target' => [ChoiceType::class, ['label' => 'Target technology', 'choices' => array_combine($targets, $targets), 'help' => 'Its projects are moved to the target, then this technology is deleted.']],
        ]);

        if (($data = $this->submitted($rename)) !== null && $this->attempt(
                fn (): Technology => $technologies->rename($slug, (string) $data['name'], $this->text($data['slug'] ?? null)),
                static fn (Technology $technology): string => sprintf('Technology renamed to "%s".', $technology->getName()),
            )) {
            return $this->redirectToRoute('admin_technology');
        }

        if (($data = $this->submitted($merge)) !== null && $this->attempt(
                fn (): int => $technologies->merge($slug, (string) $data['target']),
                static fn (int $moved): string => sprintf('"%s" merged into "%s" (%d project(s) moved).', $slug, $data['target'], $moved),
            )) {
            return $this->redirectToRoute('admin_technology');
        }

        $count = count($technology->getProjects());
        $page = $this->page($technology->getName())
            ->description(sprintf('Used by %d project(s).', $count))
            ->action(Action::link('Back', $this->generateUrl('admin_technology'), 'chevron-right'))
            ->action(Action::post(
                'Delete',
                $this->generateUrl('admin_technology_delete', ['slug' => $slug, 'detach' => $count > 0 ? 1 : null]),
                'technology_delete_' . $slug,
                $count > 0 ? sprintf('Delete it and detach it from its %d project(s)?', $count) : 'Delete this technology?',
                'trash',
            )->danger())
            ->form($rename, 'Save', $this->generateUrl('admin_technology'), 'Rename');

        if ($targets !== []) {
            $page->form($merge, 'Merge', null, 'Merge into another technology');
        }

        return $page->render($this->status($rename, $merge));
    }

    #[Route('/admin/technologies/{slug}/delete', name: 'admin_technology_delete', methods: ['POST'])]
    public function delete(string $slug): Response
    {
        $this->denyUnlessAdmin();
        $technologies = $this->get(TechnologyAdminHelper::class);

        $this->handlePost(
            'technology_delete_' . $slug,
            fn (): int => $technologies->delete($slug, $this->request()->query->getBoolean('detach')),
            static fn (int $detached): string => sprintf('Technology "%s" deleted (%d project(s) detached).', $slug, $detached),
        );

        return $this->redirectToRoute('admin_technology');
    }
}