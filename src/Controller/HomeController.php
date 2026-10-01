<?php

namespace App\Controller;

use App\Entity\Category;
use NeoPHP\Component\Controller\Contract\AbstractController;
use NeoPHP\Component\Http\Response\Response;
use NeoPHP\Component\Routing\Attribute\Route;

#[Route(path: '/', name: 'home_')]
class HomeController extends AbstractController
{
    #[Route(path: '/', name: 'index')]
    public function index(): Response
    {
        $getCategories = $this->getOrm()->getRepository(Category::class)->findAll();
        $categories = array_reduce($getCategories, static function (array $options, Category $category) {
            $options[$category->getSlug()] = $category->getName();
            return $options;
        }, []);

        $categories = ['all' => 'all'] + $categories;

        return $this->render('pages/home/index.html.twig', [
            'categories' => $categories,
            'stats' => [
                'published_projects' => 1500000,
                'active_developers' => 1290,
                'listed_technologies' => 441,
                'user_statisfaction' => 98,
            ],
            'popular_projects' => []
        ]);
    }

}