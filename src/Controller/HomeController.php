<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Category;
use App\Entity\Project;
use App\Entity\Technology;
use App\Entity\User;
use App\Entity\WebsiteContactConfig;
use App\Entity\WebsiteContactObject;
use App\Form\Website\ContactForm;
use App\Website\Helper\ContactHelper;
use NeoPHP\Component\Controller\Contract\AbstractController;
use NeoPHP\Component\Form\Contract\FormManagerInterface;
use NeoPHP\Component\Http\Request\Request;
use NeoPHP\Component\Http\Response\Response;
use NeoPHP\Component\Routing\Attribute\Route;

#[Route(path: '/', name: 'home_')]
class HomeController extends AbstractController
{
    #[Route(path: '/', name: 'index')]
    public function index(): Response
    {
        $projectRepository = $this->getOrm()->getRepository(Project::class);
        $userRepository = $this->getOrm()->getRepository(User::class);
        $technologyRepository = $this->getOrm()->getRepository(Technology::class);
        $categoryRepository = $this->getOrm()->getRepository(Category::class);

        return $this->render('pages/home/index.html.twig', [
            'categories' => $categoryRepository->findBy([], ['slug' => 'ASC']),
            'stats' => [
                'published_projects' => $projectRepository->count(),
                'active_developers' => $userRepository->count(),
                'listed_technologies' => $technologyRepository->count(),
                'categories' => $categoryRepository->count(),
            ],
            'popular_projects' => $projectRepository->findPopular(6),
        ]);
    }

    #[Route(path: '/contact', name: 'contact', methods: ['GET', 'POST'])]
    public function contact(Request $request, FormManagerInterface $formManager, ContactHelper $contactHelper): Response
    {
        $form = $formManager->createNamed('', ContactForm::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $object = $contactHelper->validate($form);

            if ($object !== null) {
                $contactHelper->save($form, $object);
                $this->addFlash('success', $this->translate('contact.flash.success'));

                return $this->redirectToRoute('home_contact');
            }
        }

        return $this->render('pages/home/contact.html.twig', [
            'form' => $form,
            'errors' => $form->isSubmitted() ? $contactHelper->errors($form) : [],
            'configs' => $this->getOrm()->getRepository(WebsiteContactConfig::class)->findEnabled(),
            'objects' => $this->getOrm()->getRepository(WebsiteContactObject::class)->findAllOrdered(),
        ]);
    }
}