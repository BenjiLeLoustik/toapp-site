<?php

namespace App\Controller;

use App\Entity\Category;
use App\Entity\Project;
use App\Entity\Technology;
use App\Entity\User;
use NeoPHP\Component\Controller\Contract\AbstractController;
use NeoPHP\Component\Http\Response\Response;
use NeoPHP\Component\Routing\Attribute\Route;
use NeoPHP\Package\Translation\Contract\TranslatorInterface;

#[Route(path: '/', name: 'home_')]
class HomeController extends AbstractController
{
    public function __construct(
        protected TranslatorInterface $translator,
    ) {
    }

    #[Route(path: '/', name: 'index')]
    public function index(): Response
    {
        $projectRepository = $this->getOrm()->getRepository(Project::class);
        $userRepository = $this->getOrm()->getRepository(User::class);
        $technologyRepository = $this->getOrm()->getRepository(Technology::class);
        $categoryRepository = $this->getOrm()->getRepository(Category::class);

        $projects = $projectRepository->findPopular(6);

        return $this->render('pages/home/index.html.twig', [
            'categories' => $categoryRepository->findBy([], ['slug' => 'ASC']),
            'stats' => [
                'published_projects' => $projectRepository->count(),
                'active_developers' => $userRepository->count(),
                'listed_technologies' => $technologyRepository->count(),
                'categories' => $categoryRepository->count(),
            ],
            'popular_projects' => $projects,
        ]);
    }

}