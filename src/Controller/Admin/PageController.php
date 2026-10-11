<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Admin\Contract\AbstractToolController;
use App\Admin\Helper\PageAdminHelper;
use App\Entity\WebsitePage;
use NeoPHP\Component\Form\Type\CheckboxType;
use NeoPHP\Component\Form\Type\ChoiceType;
use NeoPHP\Component\Form\Type\TextareaType;
use NeoPHP\Component\Form\Type\TextType;
use NeoPHP\Component\Http\Response\Response;
use NeoPHP\Component\Routing\Attribute\Route;
use NeoPHP\NeoAdmin\Page\Action;
use NeoPHP\NeoAdmin\Page\Column;

class PageController extends AbstractToolController
{
    public const COLUMNS = ['id', 'slug', 'name', 'locales', 'published', 'updated'];

    #[Route('/admin/pages', name: 'admin_page', methods: ['GET'])]
    public function index(): Response
    {
        $this->denyUnlessAdmin();
        $locale = $this->locale();
        $rows = array_map(static fn (array $row): array => array_combine(self::COLUMNS, $row), $this->get(PageAdminHelper::class)->all($locale));
        $edit = fn (array $row): string => $this->generateUrl('admin_page_edit', ['slug' => $row['slug'], 'locale' => $locale]);

        return $this->page('Pages')
            ->description('The legal pages of the site.')
            ->action(Action::link('New page', $this->generateUrl('admin_page_new'), 'plus')->primary())
            ->tabs('locale', $this->localeTabs(), $locale)
            ->table(null, $rows, [
                Column::text('name', 'Title (' . $locale . ')')->url($edit),
                Column::code('slug', 'Slug'),
                Column::text('locales', 'Locales'),
                Column::badge('published', 'Published', ['yes' => 'success', 'no' => 'warning']),
                Column::text('updated', 'Updated'),
            ], 'file', 'No page yet.', static fn (array $row): array => [
                Action::link('Edit', $edit($row), 'edit'),
            ])
            ->render();
    }

    #[Route('/admin/pages/new', name: 'admin_page_new', methods: ['GET', 'POST'])]
    public function new(): Response
    {
        $this->denyUnlessAdmin();
        $pages = $this->get(PageAdminHelper::class);
        $form = $this->createAdminForm('page_add', ['slug' => '', 'locale' => $this->defaultLocale(), 'title' => '', 'content' => '', 'meta' => '', 'published' => true], [
            'slug' => [TextType::class, ['label' => 'Slug', 'help' => 'e.g. legal-notice']],
            'locale' => [ChoiceType::class, ['label' => 'Locale', 'choices' => $this->localeChoices()]],
            'title' => [TextType::class, ['label' => 'Title']],
            'content' => [TextareaType::class, ['label' => 'Content', 'help' => 'Markdown.', 'attr' => ['rows' => 20]]],
            'meta' => [TextType::class, ['label' => 'Meta description', 'required' => false]],
            'published' => [CheckboxType::class, ['label' => 'Published', 'required' => false]],
        ]);
        $data = $this->submitted($form);

        if ($data !== null && $this->attempt(
                fn (): WebsitePage => $pages->add((string) $data['slug'], (string) $data['locale'], (string) $data['title'], (string) $data['content'], $this->text($data['meta'] ?? null), (bool) ($data['published'] ?? false)),
                static fn (WebsitePage $page): string => sprintf('Page "%s" created%s.', $page->getSlug(), $page->isPublished() ? '' : ' (unpublished)'),
            )) {
            return $this->redirectToRoute('admin_page');
        }

        return $this->page('New page')
            ->action(Action::link('Back', $this->generateUrl('admin_page'), 'chevron-right'))
            ->form($form, 'Create', $this->generateUrl('admin_page'))
            ->render($this->status($form));
    }

    #[Route('/admin/pages/{slug}', name: 'admin_page_edit', methods: ['GET', 'POST'])]
    public function edit(string $slug): Response
    {
        $this->denyUnlessAdmin();
        $pages = $this->get(PageAdminHelper::class);
        $page = $this->findOr404(static fn (): WebsitePage => $pages->get($slug));
        $locale = $this->locale();
        $translation = null;

        foreach ($page->getTranslations() as $item) {
            if ($item->getLocale() === $locale) {
                $translation = $item;
            }
        }

        $form = $this->createAdminForm('page_translate', [
            'title' => $translation?->getName() ?? '',
            'content' => $translation?->getContent() ?? '',
            'meta' => $translation?->getMetaDescription() ?? '',
        ], [
            'title' => [TextType::class, ['label' => 'Title']],
            'content' => [TextareaType::class, ['label' => 'Content', 'help' => 'Markdown.', 'attr' => ['rows' => 20]]],
            'meta' => [TextType::class, ['label' => 'Meta description', 'required' => false]],
        ]);
        $data = $this->submitted($form);

        if ($data !== null && $this->attempt(
                fn (): WebsitePage => $pages->translate($slug, $locale, (string) $data['title'], (string) $data['content'], $this->text($data['meta'] ?? null) ?? ''),
                sprintf('Translation "%s" of the page "%s" saved.', $locale, $slug),
            )) {
            return $this->redirectToRoute('admin_page_edit', ['slug' => $slug, 'locale' => $locale]);
        }

        $published = $page->isPublished();

        return $this->page($translation?->getName() ?? $slug)
            ->description(sprintf('Page "%s" · %s.', $slug, $published ? 'published' : 'unpublished'))
            ->action(Action::link('Back', $this->generateUrl('admin_page', ['locale' => $locale]), 'chevron-right'))
            ->action(Action::post(
                $published ? 'Unpublish' : 'Publish',
                $this->generateUrl('admin_page_action', ['slug' => $slug, 'action' => $published ? 'unpublish' : 'publish']),
                'page_' . $slug,
                null,
                'eye',
            ))
            ->action(Action::post('Delete', $this->generateUrl('admin_page_action', ['slug' => $slug, 'action' => 'delete']), 'page_' . $slug, 'Delete this page and all its translations?', 'trash')->danger())
            ->tabs('locale', $this->localeTabs(), $locale)
            ->form($form, 'Save the translation', null, sprintf('Translation (%s)%s', $locale, $translation === null ? ' · missing' : ''))
            ->render($this->status($form));
    }

    #[Route('/admin/pages/{slug}/{action}', name: 'admin_page_action', methods: ['POST'], requirements: ['action' => 'publish|unpublish|delete'])]
    public function action(string $slug, string $action): Response
    {
        $this->denyUnlessAdmin();
        $pages = $this->get(PageAdminHelper::class);

        $done = $this->handlePost(
            'page_' . $slug,
            static fn (): mixed => $action === 'delete' ? $pages->delete($slug) : $pages->publish($slug, $action === 'publish'),
            sprintf('Page "%s" %s.', $slug, ['publish' => 'published', 'unpublish' => 'unpublished', 'delete' => 'deleted'][$action]),
        );

        return $done && $action === 'delete' ? $this->redirectToRoute('admin_page') : $this->redirectToRoute('admin_page_edit', ['slug' => $slug]);
    }
}