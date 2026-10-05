<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Project;
use App\Project\Enum\ProjectDateEnum;
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
    public const PERIODS = [ProjectDateEnum::WEEK, ProjectDateEnum::MONTH, ProjectDateEnum::YEAR];

    public function __construct(
        protected EntityManagerInterface $entityManager,
    ) {
    }

    #[Route('/', name: '_index', methods: ['GET'])]
    public function index(
        Request $request,
        #[MapPagination(defaultLimit: 9, maxLimit: 48)]
        PageRequest $pageRequest,
    ): Response {
        $period = ProjectDateEnum::tryFrom((string) $request->query->get('period', '')) ?? ProjectDateEnum::WEEK;

        if (!in_array($period, self::PERIODS, true)) {
            $period = ProjectDateEnum::WEEK;
        }

        return $this->render('pages/trend/index.html.twig', [
            'period' => $period,
            'periods' => self::PERIODS,
            'projects' => $this->paginate(
                $this->entityManager->getRepository(Project::class)->createTrendingQueryBuilder($period),
                $pageRequest
            ),
        ]);
    }
}