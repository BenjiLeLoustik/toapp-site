<?php

declare(strict_types=1);

namespace App\Controller;

use NeoPHP\Component\Controller\Contract\AbstractController;
use NeoPHP\Component\Http\Response\Response;
use NeoPHP\Component\Routing\Attribute\Route;

#[Route(path: '/categories', name: 'category')]
class CategoryController extends AbstractController
{
    #[Route('/', name: '_index', methods: ['GET'])]
    public function index(): Response
    {
        return $this->render('pages/category/index.html.twig', [

        ]);
    }
}
