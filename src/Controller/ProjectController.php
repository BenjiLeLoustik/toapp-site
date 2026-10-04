<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\ProjectShare;
use App\Entity\User;
use App\Helper\View\FormatNumberViewHelper;
use App\Project\Enum\ProjectShareEnum;
use App\Project\Helper\ProjectHelper;
use NeoPHP\Component\Controller\Contract\AbstractController;
use NeoPHP\Component\Http\Request\Request;
use NeoPHP\Component\Http\Response\Response;
use NeoPHP\Component\Routing\Attribute\Route;
use NeoPHP\Package\Orm\Contract\EntityManagerInterface;

#[Route('/project', name: 'project_')]
class ProjectController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private ProjectHelper $projectHelper,
    ) {
    }

    #[Route('/{id}/{slug}', name: 'show')]
    public function showProject(int $id, string $slug, Request $request): Response
    {
        $project = $this->projectHelper->validateProject($id, $slug);
        if (!$project) {
            return $this->redirectToRoute('home_index');
        }

        $user = $this->getUser();
        $user = $user instanceof User ? $user : null;

        $this->projectHelper->addView($project, $this->getUser(), $request->getClientIp());

        return $this->render('pages/project/show.html.twig', [
            'project' => $project,
            'shareTypes' => ProjectShareEnum::cases(),
            'url' => $request->getUri(),
            'isLiked' => $this->projectHelper->isLiked($project, $user),
            'isFavorite' => $this->projectHelper->isFavorite($project, $user),
        ]);
    }

    #[Route('/api/{id}/{slug}/share', name: 'api_share', methods: ['POST'])]
    public function api_projectShare(int $id, string $slug, Request $request, FormatNumberViewHelper $formatNumber): Response
    {
        $project = $this->projectHelper->validateProject($id, $slug);
        if (!$project) {
            return $this->json(['success' => false]);
        }

        $this->projectHelper->addShare(
            $project,
            ProjectShareEnum::from($request->get('type')),
            $this->getUser(),
            $request->getClientIp(),
        );

        $totalShares = $this->entityManager->getRepository(ProjectShare::class)->count(['project' => $project]);

        return $this->json([
            'success' => true,
            'totalShares' => $formatNumber->__invoke($totalShares),
        ]);
    }

    #[Route('/api/{id}/{slug}/like', name: 'api_like', methods: ['POST'])]
    public function api_projectLike(int $id, string $slug, FormatNumberViewHelper $formatNumber): Response
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            return $this->json(['success' => false], 401);
        }

        $project = $this->projectHelper->validateProject($id, $slug);
        if (!$project) {
            return $this->json(['success' => false], 404);
        }

        $liked = $this->projectHelper->toggleLike($project, $user);
        $totalLikes = $this->projectHelper->countLikes($project);

        return $this->json([
            'success' => true,
            'liked' => $liked,
            'totalLikes' => $formatNumber->__invoke($totalLikes),
            'likesLabel' => $this->translate('project.show.stats.likes', ['count' => $totalLikes]),
        ]);
    }

    #[Route('/api/{id}/{slug}/favorite', name: 'api_favorite', methods: ['POST'])]
    public function api_projectFavorite(int $id, string $slug, FormatNumberViewHelper $formatNumber): Response
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            return $this->json(['success' => false], 401);
        }

        $project = $this->projectHelper->validateProject($id, $slug);
        if (!$project) {
            return $this->json(['success' => false], 404);
        }

        $favorite = $this->projectHelper->toggleFavorite($project, $user);

        return $this->json([
            'success' => true,
            'favorite' => $favorite,
        ]);
    }
}
