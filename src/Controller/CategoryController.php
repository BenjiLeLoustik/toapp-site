<?php

namespace App\Controller;

use App\Entity\Category;
use App\Entity\Project;
use App\Entity\Technology;
use App\Project\Enum\ProjectDateEnum;
use App\Project\Enum\ProjectSortEnum;
use App\Project\Search\ProjectSearchFilters;
use NeoPHP\Component\Api\Attribute\MapPagination;
use NeoPHP\Component\Api\Pagination\PageRequest;
use NeoPHP\Component\Controller\Contract\AbstractController;
use NeoPHP\Component\Http\Request\Request;
use NeoPHP\Component\Http\Response\Response;
use NeoPHP\Component\Routing\Attribute\Route;
use NeoPHP\Package\Orm\Contract\EntityManagerInterface;

#[Route(path: '/categories', name: 'category')]
class CategoryController extends AbstractController
{
    public function __construct(
        protected EntityManagerInterface $entityManager,
    ) {
    }

    #[Route('/', name: '_index', methods: ['GET'])]
    public function index(
        #[MapPagination(defaultLimit: 6, maxLimit: 48)]
        PageRequest $pageRequest,
    ): Response {
        $queryBuilder = $this->entityManager->getRepository(Category::class)
            ->createQueryBuilder('c')
            ->orderBy('c.slug', 'ASC');

        return $this->render('pages/category/index.html.twig', [
            'categories' => $this->paginate($queryBuilder, $pageRequest),
        ]);
    }

    #[Route('/{slug}', name: '_show', methods: ['GET'])]
    public function show(
        string $slug,
        Request $request,
        #[MapPagination(defaultLimit: 6, maxLimit: 48)]
        PageRequest $pageRequest,
    ): Response {
        $query = $request->query->all();
        $selectedCategory = $query['category'] ?? $slug;

        if (is_string($selectedCategory) && $selectedCategory !== $slug) {
            unset($query['category'], $query['page']);

            return $this->redirectToRoute('category_show', ['slug' => $selectedCategory] + $query);
        }

        /** @var null|Category $category */
        $category = $this->entityManager->getRepository(Category::class)->findOneBy(['slug' => $slug]);

        if ($category === null) {
            throw $this->createNotFoundException('The category "{slug}" does not exist.', ['slug' => $slug]);
        }

        $filters = ProjectSearchFilters::fromQuery($query);

        return $this->render('pages/category/show.html.twig', [
            'category' => $category,
            'categories' => $this->entityManager->getRepository(Category::class)->findBy([], ['slug' => 'ASC']),
            'technologies' => $this->entityManager->getRepository(Technology::class)->findUsedInCategory($category),
            'filters' => $filters,
            'sorts' => ProjectSortEnum::cases(),
            'dates' => ProjectDateEnum::cases(),
            'projects' => $this->paginate(
                $this->entityManager->getRepository(Project::class)->createSearchQueryBuilder($category, $filters),
                $pageRequest
            ),
        ]);
    }
}