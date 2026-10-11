<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Admin\Contract\AbstractToolController;
use App\Admin\Helper\CategoryAdminHelper;
use App\Entity\Category;
use NeoPHP\Component\Form\Type\ChoiceType;
use NeoPHP\Component\Form\Type\TextareaType;
use NeoPHP\Component\Form\Type\TextType;
use NeoPHP\Component\Http\Response\Response;
use NeoPHP\Component\Routing\Attribute\Route;
use NeoPHP\NeoAdmin\Page\Action;
use NeoPHP\NeoAdmin\Page\Column;

class CategoryController extends AbstractToolController
{
    #[Route('/admin/categories', name: 'admin_category', methods: ['GET', 'POST'])]
    public function index(): Response
    {
        $this->denyUnlessAdmin();
        $categories = $this->get(CategoryAdminHelper::class);
        $locale = $this->locale();
        $form = $this->createAdminForm('category_add', ['slug' => '', 'icon' => '', 'locale' => $locale, 'name' => '', 'description' => ''], [
            'slug' => [TextType::class, ['label' => 'Slug', 'help' => 'e.g. web-apps']],
            'icon' => [TextType::class, ['label' => 'Lucide icon', 'help' => 'e.g. globe, smartphone']],
            'locale' => [ChoiceType::class, ['label' => 'Locale', 'choices' => $this->localeChoices()]],
            'name' => [TextType::class, ['label' => 'Name']],
            'description' => [TextareaType::class, ['label' => 'Description', 'required' => false, 'attr' => ['rows' => 3]]],
        ]);
        $data = $this->submitted($form);

        if ($data !== null && $this->attempt(
                static fn (): Category => $categories->add((string) $data['slug'], (string) $data['icon'], (string) $data['locale'], (string) $data['name'], (string) ($data['description'] ?? '')),
                static fn (Category $category): string => sprintf('Category "%s" added.', $category->getSlug()),
            )) {
            return $this->redirectToRoute('admin_category', ['locale' => $locale]);
        }

        $edit = fn (array $row): string => $this->generateUrl('admin_category_edit', ['slug' => $row['slug'], 'locale' => $locale]);

        return $this->page('Categories')
            ->description('The categories of the projects.')
            ->tabs('locale', $this->localeTabs(), $locale)
            ->table(null, $categories->all($locale), [
                Column::text('name', 'Name (' . $locale . ')')->url($edit),
                Column::code('slug', 'Slug'),
                Column::code('icon', 'Icon'),
                Column::text('locales', 'Locales'),
                Column::number('projects', 'Projects'),
            ], 'folder', 'No category yet.', static fn (array $row): array => [
                Action::link('Edit', $edit($row), 'edit'),
            ])
            ->form($form, 'Add', null, 'New category')
            ->render($this->status($form));
    }

    #[Route('/admin/categories/{slug}', name: 'admin_category_edit', methods: ['GET', 'POST'])]
    public function edit(string $slug): Response
    {
        $this->denyUnlessAdmin();
        $categories = $this->get(CategoryAdminHelper::class);
        $category = $this->findOr404(static fn (): Category => $categories->get($slug));
        $locale = $this->locale();
        $translation = null;

        foreach ($category->getTranslations() as $item) {
            if ($item->getLocale() === $locale) {
                $translation = $item;
            }
        }

        $targets = array_values(array_diff($categories->slugs(), [$slug]));
        $count = count($category->getProjects());

        $settings = $this->createAdminForm('category_edit', ['icon' => $category->getIcon(), 'slug' => $category->getSlug()], [
            'icon' => [TextType::class, ['label' => 'Lucide icon']],
            'slug' => [TextType::class, ['label' => 'Slug']],
        ]);
        $translate = $this->createAdminForm('category_translate', ['name' => $translation?->getName() ?? '', 'description' => $translation?->getDescription() ?? ''], [
            'name' => [TextType::class, ['label' => 'Name']],
            'description' => [TextareaType::class, ['label' => 'Description', 'required' => false, 'attr' => ['rows' => 4]]],
        ]);
        $delete = $this->createAdminForm('category_delete', ['move_to' => null], [
            'move_to' => [ChoiceType::class, [
                'label' => 'Move its projects to',
                'choices' => array_combine($targets, $targets),
                'required' => $count > 0,
                'help' => $count > 0 ? sprintf('This category contains %d project(s).', $count) : 'This category has no project.',
            ]],
        ]);

        if (($data = $this->submitted($settings)) !== null && $this->attempt(
                fn (): Category => $categories->edit($slug, $this->text($data['icon'] ?? null), $this->text($data['slug'] ?? null)),
                static fn (Category $category): string => sprintf('Category "%s" updated.', $category->getSlug()),
            )) {
            return $this->redirectToRoute('admin_category_edit', ['slug' => $category->getSlug(), 'locale' => $locale]);
        }

        if (($data = $this->submitted($translate)) !== null && $this->attempt(
                static fn (): Category => $categories->translate($slug, $locale, (string) $data['name'], (string) ($data['description'] ?? '')),
                sprintf('Translation "%s" saved.', $locale),
            )) {
            return $this->redirectToRoute('admin_category_edit', ['slug' => $slug, 'locale' => $locale]);
        }

        if (($data = $this->submitted($delete)) !== null && $this->attempt(
                fn (): int => $categories->delete($slug, $this->text($data['move_to'] ?? null)),
                static fn (int $moved): string => sprintf('Category "%s" deleted (%d project(s) moved).', $slug, $moved),
            )) {
            return $this->redirectToRoute('admin_category');
        }

        return $this->page($translation?->getName() ?? $category->getSlug())
            ->description(sprintf('Category "%s" · %d project(s).', $category->getSlug(), $count))
            ->action(Action::link('Back', $this->generateUrl('admin_category', ['locale' => $locale]), 'chevron-right'))
            ->form($settings, 'Save', null, 'Settings')
            ->tabs('locale', $this->localeTabs(), $locale)
            ->form($translate, 'Save the translation', null, sprintf('Translation (%s)%s', $locale, $translation === null ? ' · missing' : ''))
            ->form($delete, 'Delete the category', null, 'Delete')
            ->render($this->status($settings, $translate, $delete));
    }
}