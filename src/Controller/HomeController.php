<?php

namespace App\Controller;

use App\Entity\Category;
use App\Entity\CategoryTranslated;
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
        $translations = $this->getOrm()
            ->getRepository(CategoryTranslated::class)
            ->findAllTranslated($this->translator->getLocale());

        $categories = array_reduce($translations, static function (array $options, CategoryTranslated $translation): array {
            $options[$translation->getCategory()->getSlug()] = $translation->getName();
            return $options;
        }, []);

        $categories = [
            'all' => $this->translator->translate('categories.all'),
        ] + $categories;

        $projectRepository = $this->getOrm()->getRepository(Project::class);
        $userRepository = $this->getOrm()->getRepository(User::class);
        $technologyRepository = $this->getOrm()->getRepository(Technology::class);
        $categoryRepository = $this->getOrm()->getRepository(Category::class);

        $projects = $projectRepository->findPopular(6);

        return $this->render('pages/home/index.html.twig', [
            'categories' => $categories,
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