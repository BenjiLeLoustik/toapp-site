<?php

declare(strict_types=1);

namespace App\Admin\Helper;

use App\Admin\Exception\AdminException;
use App\Entity\WebsitePage;
use App\Entity\WebsitePageTranslated;
use App\Helper\SlugHelper;
use NeoPHP\Package\Orm\Contract\EntityManagerInterface;

class PageAdminHelper
{
    use AdminValidationTrait;

    public function __construct(
        private EntityManagerInterface $entityManager,
    ) {
    }

    public function all(string $locale): array
    {
        return array_map(fn (WebsitePage $page): array => [
            $page->getId(),
            $page->getSlug(),
            $this->findTranslation($page->getTranslations(), $locale)?->getName() ?? '-',
            $this->locales($page->getTranslations()),
            $page->isPublished() ? 'yes' : 'no',
            $page->getUpdatedAt()?->format('Y-m-d H:i') ?? '-',
        ], $this->entityManager->getRepository(WebsitePage::class)->findBy([], ['slug' => 'ASC']));
    }

    public function slugs(): array
    {
        return array_map(
            static fn (WebsitePage $page): string => $page->getSlug(),
            $this->entityManager->getRepository(WebsitePage::class)->findBy([], ['slug' => 'ASC'])
        );
    }

    public function get(string $slug): WebsitePage
    {
        $page = $this->entityManager->getRepository(WebsitePage::class)->findOneBy(['slug' => $slug]);

        if (!$page instanceof WebsitePage) {
            throw new AdminException('The page "{slug}" does not exist.', 0, null, ['slug' => $slug]);
        }

        return $page;
    }

    public function add(string $slug, string $locale, string $title, string $content, ?string $metaDescription, bool $published): WebsitePage
    {
        $slug = SlugHelper::slugify($slug);

        if ($slug === '') {
            throw new AdminException('The slug of the page cannot be empty.');
        }

        if ($this->entityManager->getRepository(WebsitePage::class)->findOneBy(['slug' => $slug]) !== null) {
            throw new AdminException('A page with the slug "{slug}" already exists.', 0, null, ['slug' => $slug]);
        }

        $page = (new WebsitePage())
            ->setSlug($slug)
            ->setPublished($published)
            ->setUpdatedAt(new \DateTime());

        $this->entityManager->persist($page);
        $this->applyTranslation($page, $locale, $title, $content, $metaDescription);
        $this->entityManager->flush();

        return $page;
    }

    public function translate(string $slug, string $locale, ?string $title, ?string $content, ?string $metaDescription): WebsitePage
    {
        $page = $this->get($slug);
        $existing = $this->findTranslation($page->getTranslations(), $this->locale($locale));

        $this->applyTranslation(
            $page,
            $locale,
            $title ?? $existing?->getName() ?? '',
            $content ?? $existing?->getContent() ?? '',
            $metaDescription ?? $existing?->getMetaDescription()
        );

        $page->setUpdatedAt(new \DateTime());
        $this->entityManager->flush();

        return $page;
    }

    public function publish(string $slug, bool $published): WebsitePage
    {
        $page = $this->get($slug)->setPublished($published);
        $this->entityManager->flush();

        return $page;
    }

    public function delete(string $slug): void
    {
        $this->entityManager->remove($this->get($slug));
        $this->entityManager->flush();
    }

    public function readFile(string $path): string
    {
        if (!is_file($path) || !is_readable($path)) {
            throw new AdminException('The file "{path}" cannot be read.', 0, null, ['path' => $path]);
        }

        return (string) file_get_contents($path);
    }

    private function applyTranslation(WebsitePage $page, string $locale, string $title, string $content, ?string $metaDescription): void
    {
        $locale = $this->locale($locale);
        $title = $this->notEmpty($title, 'title of the page');
        $content = $this->notEmpty($content, 'content of the page');

        $translation = $this->findTranslation($page->getTranslations(), $locale);

        if (!$translation instanceof WebsitePageTranslated) {
            $translation = (new WebsitePageTranslated())->setLocale($locale);
            $page->addTranslation($translation);
            $this->entityManager->persist($translation);
        }

        $metaDescription = $metaDescription !== null ? trim($metaDescription) : null;

        $translation
            ->setName($title)
            ->setContent($content)
            ->setMetaDescription($metaDescription !== null && $metaDescription !== '' ? mb_substr($metaDescription, 0, 255) : null);
    }
}