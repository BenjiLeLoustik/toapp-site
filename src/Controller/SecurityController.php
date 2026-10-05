<?php

declare(strict_types=1);

namespace App\Controller;

use App\Form\Security\LoginForm;
use App\Form\Security\RegistrationForm;
use App\Security\Helper\RegistrationHelper;
use NeoPHP\Component\Controller\Contract\AbstractController;
use NeoPHP\Component\Form\Contract\FormManagerInterface;
use NeoPHP\Component\Http\Request\Request;
use NeoPHP\Component\Http\Response\Response;
use NeoPHP\Component\Routing\Attribute\Route;
use NeoPHP\Package\Security\Exception\SecurityException;

#[Route('/', name: 'app_security')]
class SecurityController extends AbstractController
{
    #[Route('/login', name: '_login', methods: ['GET', 'POST'])]
    public function login(FormManagerInterface $formManager): Response
    {
        if ($this->getUser() !== null) {
            return $this->redirectToRoute('home_index');
        }

        $form = $formManager->createNamed('', LoginForm::class);

        return $this->render('security/login.html.twig', [
            'form' => $form,
            'last_username' => $this->getLastUsername(),
            'error' => $this->getLastAuthenticationError(),
        ]);
    }

    #[Route('/register', name: '_register', methods: ['GET', 'POST'])]
    public function register(Request $request, FormManagerInterface $formManager, RegistrationHelper $registrationHelper): Response
    {
        if ($this->getUser() !== null) {
            return $this->redirectToRoute('home_index');
        }

        $form = $formManager->createNamed('', RegistrationForm::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid() && $registrationHelper->validate($form)) {
            $user = $registrationHelper->register($form);

            $this->loginUser($user);
            $this->addFlash('success', $this->translate('register.flash.success', ['name' => $user->getFirstname()]));

            return $this->redirectToRoute('home_index');
        }

        return $this->render('security/register.html.twig', [
            'form' => $form,
            'errors' => $form->isSubmitted() ? $registrationHelper->errors($form) : [],
        ]);
    }

    #[Route('/logout', name: '_logout', methods: ['GET', 'POST'])]
    public function logout(): Response
    {
        throw new SecurityException('This route is handled by the "logout" option of the firewall in config/packages/security.yaml.');
    }
}