<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\WebsitePage;
use NeoPHP\Component\Controller\Contract\AbstractController;
use NeoPHP\Component\Http\Response\Response;
use NeoPHP\Component\Routing\Attribute\Route;
use NeoPHP\Package\Orm\Contract\EntityManagerInterface;
use NeoPHP\Package\Translation\TranslationManagerInterface;

#[Route(path: '/legal', name: 'legal')]
class LegalController extends AbstractController
{
    public const FALLBACK_LOCALE = 'en';

    public function __construct(
        protected EntityManagerInterface $entityManager,
        protected TranslationManagerInterface $translator,
    ) {
    }

    #[Route('/{slug}', name: '_show', methods: ['GET'])]
    public function show(string $slug): Response
    {
        $page = $this->entityManager->getRepository(WebsitePage::class)->findPublishedBySlug($slug);

        if ($page === null) {
            throw $this->createNotFoundException('The page "{slug}" does not exist.', ['slug' => $slug]);
        }

        $translation = $page->getTranslation($this->translator->getLocale())
            ?? $page->getTranslation(self::FALLBACK_LOCALE);

        if ($translation === null) {
            throw $this->createNotFoundException('The page "{slug}" has no translation.', ['slug' => $slug]);
        }

        return $this->render('pages/legal/show.html.twig', [
            'page' => $page,
            'translation' => $translation,
        ]);
    }
}