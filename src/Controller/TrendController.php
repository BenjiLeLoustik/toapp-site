<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Project;
use App\Entity\User;
use App\Trend\Enum\TrendCreatorSortEnum;
use App\Trend\Enum\TrendProjectSortEnum;
use App\Trend\Enum\TrendTabEnum;
use NeoPHP\Component\Api\Attribute\MapPagination;
use NeoPHP\Component\Api\Pagination\PageRequest;
use NeoPHP\Component\Controller\Contract\AbstractController;
use NeoPHP\Component\Http\Request\Request;
use NeoPHP\Component\Http\Response\Response;
use NeoPHP\Component\Routing\Attribute\Route;
use NeoPHP\Package\Orm\Contract\EntityManagerInterface;

#[Route(path: '/trends', name: 'trend')]
class TrendController extends AbstractController
{
    public function __construct(
        protected EntityManagerInterface $entityManager,
    ) {
    }

    #[Route('/', name: '_index', methods: ['GET'])]
    public function index(
        Request $request,
        #[MapPagination(defaultLimit: 10, maxLimit: 50)]
        PageRequest $pageRequest,
    ): Response {
        $tab = TrendTabEnum::tryFrom((string) $request->query->get('tab', '')) ?? TrendTabEnum::PROJECTS;
        $search = trim((string) $request->query->get('search', ''));
        $sortValue = (string) $request->query->get('sort', '');

        if ($tab === TrendTabEnum::CREATORS) {
            $sort = TrendCreatorSortEnum::tryFrom($sortValue) ?? TrendCreatorSortEnum::PROJECTS;
            $sorts = TrendCreatorSortEnum::cases();
            $queryBuilder = $this->entityManager->getRepository(User::class)->createTopCreatorsQueryBuilder($search, $sort);
        } else {
            $sort = TrendProjectSortEnum::tryFrom($sortValue) ?? TrendProjectSortEnum::VIEWS;
            $sorts = TrendProjectSortEnum::cases();
            $queryBuilder = $this->entityManager->getRepository(Project::class)->createTrendingProjectsQueryBuilder($search, $sort);
        }

        return $this->render('pages/trend/index.html.twig', [
            'tab' => $tab,
            'tabs' => TrendTabEnum::cases(),
            'search' => $search,
            'sort' => $sort,
            'sorts' => $sorts,
            'items' => $this->paginate($queryBuilder, $pageRequest),
        ]);
    }
}