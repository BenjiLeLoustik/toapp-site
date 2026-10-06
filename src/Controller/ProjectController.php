<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\ProjectShare;
use App\Entity\User;
use App\Helper\View\FormatNumberViewHelper;
use App\Project\Enum\ProjectShareEnum;
use App\Project\Helper\ProjectHelper;
use App\Entity\Category;
use App\Entity\Project;
use App\Entity\Technology;
use App\Project\Enum\ProjectDateEnum;
use App\Project\Enum\ProjectSortEnum;
use NeoPHP\Component\Controller\Contract\AbstractController;
use NeoPHP\Component\Http\Request\Request;
use NeoPHP\Component\Http\Response\Response;
use NeoPHP\Component\Routing\Attribute\Route;
use NeoPHP\Package\Orm\Contract\EntityManagerInterface;
use App\Project\Search\ProjectSearchFilters;
use NeoPHP\Component\Api\Attribute\MapPagination;
use NeoPHP\Component\Api\Pagination\PageRequest;

#[Route('/projects', name: 'project_')]
class ProjectController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private ProjectHelper $projectHelper,
    ) {
    }

    #[Route('', name: 'index', methods: ['GET'])]
    public function index(
        Request $request,
        #[MapPagination(defaultLimit: 6, maxLimit: 48)]
        PageRequest $pageRequest,
    ): Response {
        $query = $request->query->all();
        $redirect = ProjectSearchFilters::categoryRedirect($query, null);

        if ($redirect !== null) {
            return $this->redirectToRoute(...$redirect);
        }

        $filters = ProjectSearchFilters::fromQuery($query);

        return $this->render('pages/project/index.html.twig', [
            'categories' => $this->entityManager->getRepository(Category::class)->findBy([], ['slug' => 'ASC']),
            'technologies' => $this->entityManager->getRepository(Technology::class)->findUsedInCategory(),
            'filters' => $filters,
            'sorts' => ProjectSortEnum::cases(),
            'dates' => ProjectDateEnum::cases(),
            'projects' => $this->paginate(
                $this->entityManager->getRepository(Project::class)->createSearchQueryBuilder(null, $filters),
                $pageRequest
            ),
        ]);
    }

    #[Route('/{id}/{slug}', name: 'show')]
    public function showProject(int $id, string $slug, Request $request): Response
    {
        $user = $this->currentUser();
        $project = $this->projectHelper->validateProject($id, $slug, $user);

        if (!$project) {
            return $this->redirectToRoute('home_index');
        }

        $this->projectHelper->addView($project, $user, $request);

        return $this->render('pages/project/show.html.twig', [
            'project' => $project,
            'links' => $this->projectHelper->links($project),
            'isOwner' => $this->projectHelper->isOwner($project, $user),
            'shareTypes' => ProjectShareEnum::cases(),
            'url' => $request->getUri(),
            'isLiked' => $this->projectHelper->isLiked($project, $user),
            'isFavorite' => $this->projectHelper->isFavorite($project, $user),
        ]);
    }

    #[Route('/api/{id}/{slug}/share', name: 'api_share', methods: ['POST'])]
    public function api_projectShare(int $id, string $slug, Request $request, FormatNumberViewHelper $formatNumber): Response
    {
        $user = $this->currentUser();
        $project = $this->projectHelper->validateProject($id, $slug, $user);

        if (!$project) {
            return $this->json(['success' => false], 404);
        }

        $type = ProjectShareEnum::tryFrom((string) $request->get('type'));

        if ($type === null) {
            return $this->json(['success' => false], 400);
        }

        $this->projectHelper->addShare($project, $type, $user, $request->getClientIp());

        $totalShares = $this->entityManager->getRepository(ProjectShare::class)->count(['project' => $project]);

        return $this->json([
            'success' => true,
            'totalShares' => $formatNumber->__invoke($totalShares),
        ]);
    }

    #[Route('/api/{id}/{slug}/like', name: 'api_like', methods: ['POST'])]
    public function api_projectLike(int $id, string $slug, FormatNumberViewHelper $formatNumber): Response
    {
        $user = $this->currentUser();

        if ($user === null) {
            return $this->json(['success' => false], 401);
        }

        $project = $this->projectHelper->validateProject($id, $slug, $user);

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
    public function api_projectFavorite(int $id, string $slug): Response
    {
        $user = $this->currentUser();

        if ($user === null) {
            return $this->json(['success' => false], 401);
        }

        $project = $this->projectHelper->validateProject($id, $slug, $user);

        if (!$project) {
            return $this->json(['success' => false], 404);
        }

        return $this->json([
            'success' => true,
            'favorite' => $this->projectHelper->toggleFavorite($project, $user),
        ]);
    }

    private function currentUser(): ?User
    {
        $user = $this->getUser();

        return $user instanceof User ? $user : null;
    }
}