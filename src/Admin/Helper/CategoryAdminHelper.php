<?php

declare(strict_types=1);

namespace App\Admin\Helper;

use App\Admin\Exception\AdminException;
use App\Entity\Category;
use App\Entity\CategoryTranslated;
use App\Helper\SlugHelper;
use NeoPHP\Package\Orm\Contract\EntityManagerInterface;

class CategoryAdminHelper
{
    public function __construct(
        private EntityManagerInterface $entityManager,
    ) {
    }

    public function all(string $locale): array
    {
        return array_map(function (Category $category) use ($locale): array {
            $translation = $this->translation($category, $locale);

            return [
                'id' => $category->getId(),
                'slug' => $category->getSlug(),
                'icon' => $category->getIcon(),
                'name' => $translation?->getName() ?? '-',
                'locales' => implode(', ', array_map(
                    static fn (CategoryTranslated $item): string => (string) $item->getLocale(),
                    iterator_to_array($category->getTranslations())
                )),
                'projects' => count($category->getProjects()),
            ];
        }, $this->entityManager->getRepository(Category::class)->findBy([], ['slug' => 'ASC']));
    }

    public function slugs(): array
    {
        return array_map(
            static fn (Category $category): string => $category->getSlug(),
            $this->entityManager->getRepository(Category::class)->findBy([], ['slug' => 'ASC'])
        );
    }

    public function get(string $slug): Category
    {
        $category = $this->entityManager->getRepository(Category::class)->findOneBy(['slug' => $slug]);

        if (!$category instanceof Category) {
            throw new AdminException(sprintf('The category "%s" does not exist.', $slug));
        }

        return $category;
    }

    public function add(string $slug, string $icon, string $locale, string $name, string $description): Category
    {
        $slug = SlugHelper::slugify($slug);
        $this->assertFreeSlug($slug);

        $category = (new Category())
            ->setSlug($slug)
            ->setIcon($this->icon($icon));

        $this->entityManager->persist($category);
        $this->applyTranslation($category, $locale, $name, $description);
        $this->entityManager->flush();

        return $category;
    }

    public function edit(string $slug, ?string $icon, ?string $newSlug): Category
    {
        $category = $this->get($slug);

        if ($icon !== null) {
            $category->setIcon($this->icon($icon));
        }

        if ($newSlug !== null) {
            $newSlug = SlugHelper::slugify($newSlug);

            if ($newSlug !== $slug) {
                $this->assertFreeSlug($newSlug);
                $category->setSlug($newSlug);
            }
        }

        $this->entityManager->flush();

        return $category;
    }

    public function translate(string $slug, string $locale, string $name, string $description): Category
    {
        $category = $this->get($slug);
        $this->applyTranslation($category, $locale, $name, $description);
        $this->entityManager->flush();

        return $category;
    }

    public function delete(string $slug, ?string $moveTo = null): int
    {
        $category = $this->get($slug);
        $projects = iterator_to_array($category->getProjects());
        $target = null;

        if ($moveTo !== null) {
            if ($moveTo === $slug) {
                throw new AdminException('The target category must be different from the deleted one.');
            }

            $target = $this->get($moveTo);
        }

        if ($projects !== [] && $target === null) {
            throw new AdminException(sprintf('The category "%s" contains %d project(s): use --move-to=<slug> to move them.', $slug, count($projects)));
        }

        foreach ($projects as $project) {
            $project->setCategory($target);
        }

        $this->entityManager->remove($category);
        $this->entityManager->flush();

        return count($projects);
    }

    private function applyTranslation(Category $category, string $locale, string $name, string $description): void
    {
        $locale = strtolower(trim($locale));
        $name = trim($name);
        $description = trim($description);

        if (preg_match('/^[a-z]{2}(_[A-Z]{2})?$/', $locale) !== 1) {
            throw new AdminException(sprintf('The locale "%s" is not valid (e.g. en, fr).', $locale));
        }

        if ($name === '') {
            throw new AdminException('The name of the category cannot be empty.');
        }

        $translation = $this->translation($category, $locale);

        if ($translation === null) {
            $translation = (new CategoryTranslated())
                ->setCategory($category)
                ->setLocale($locale);

            $category->getTranslations()->add($translation);
            $this->entityManager->persist($translation);
        }

        $translation
            ->setName($name)
            ->setDescription($description);
    }

    private function translation(Category $category, string $locale): ?CategoryTranslated
    {
        foreach ($category->getTranslations() as $translation) {
            if ($translation->getLocale() === $locale) {
                return $translation;
            }
        }

        return null;
    }

    private function icon(string $icon): string
    {
        $icon = strtolower(trim($icon));

        if (preg_match('/^[a-z0-9-]+$/', $icon) !== 1) {
            throw new AdminException(sprintf('The icon "%s" is not a valid Lucide icon name (e.g. layout-dashboard).', $icon));
        }

        return $icon;
    }

    private function assertFreeSlug(string $slug): void
    {
        if ($slug === '') {
            throw new AdminException('The slug of the category cannot be empty.');
        }

        if ($this->entityManager->getRepository(Category::class)->findOneBy(['slug' => $slug]) !== null) {
            throw new AdminException(sprintf('A category with the slug "%s" already exists.', $slug));
        }
    }
}