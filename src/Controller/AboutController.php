<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\ProjectFavorite;
use App\Entity\ProjectLike;
use App\Entity\ProjectShare;
use App\Entity\ProjectView;
use App\Entity\User;
use NeoPHP\Component\Controller\Contract\AbstractController;
use NeoPHP\Component\Http\Response\Response;
use NeoPHP\Component\Routing\Attribute\Route;
use NeoPHP\Package\Orm\Contract\EntityManagerInterface;

#[Route(path: '/about', name: 'about')]
class AboutController extends AbstractController
{
    public function __construct(
        protected EntityManagerInterface $entityManager,
    ) {
    }

    #[Route('/', name: '_index', methods: ['GET'])]
    public function index(): Response
    {
        return $this->render('pages/about/index.html.twig', [
            'stats' => [
                'likes' => $this->entityManager->getRepository(ProjectLike::class)->count(),
                'views' => $this->entityManager->getRepository(ProjectView::class)->count(),
                'shares' => $this->entityManager->getRepository(ProjectShare::class)->count(),
                'favorites' => $this->entityManager->getRepository(ProjectFavorite::class)->count(),
            ],
            'contributors' => $this->entityManager->getRepository(User::class)->findTopContributors(8),
        ]);
    }
}