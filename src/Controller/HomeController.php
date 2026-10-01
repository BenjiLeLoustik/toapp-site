<?php

namespace App\Controller;

use NeoPHP\Component\Controller\Contract\AbstractController;
use NeoPHP\Component\Http\Response\Response;
use NeoPHP\Component\Routing\Attribute\Route;

#[Route(path: '/', name: 'home_')]
class HomeController extends AbstractController
{

    #[Route(path: '/', name: 'index')]
    public function index(): Response
    {
        return $this->render('pages/home/index.html.twig', [
            'stats' => [
                'published_projects' => 1500000,
                'active_developers' => 1290,
                'listed_technologies' => 441,
                'user_statisfaction' => 98,
            ]
        ]);
    }

}