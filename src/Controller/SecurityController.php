<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\User;
use App\Form\Security\LoginForm;
use App\Form\Security\RegistrationForm;
use App\Security\Helper\RegistrationHelper;
use App\User\Helper\EmailVerificationHelper;
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

    #[Route('/verify-email', name: '_verify_email', methods: ['GET'])]
    public function verifyEmail(Request $request, EmailVerificationHelper $verification): Response
    {
        $result = $verification->verify(
            (int) $request->query->get('id', 0),
            (int) $request->query->get('expires', 0),
            (string) $request->query->get('signature', '')
        );

        $this->addFlash($result->flashType(), $this->translate($result->translationKey()));

        return $this->redirectToRoute($this->getUser() !== null ? 'home_index' : 'app_security_login');
    }

    #[Route('/verify-email/resend', name: '_verify_email_resend', methods: ['POST'])]
    public function resendVerificationEmail(Request $request, EmailVerificationHelper $verification): Response
    {
        $user = $this->getUser();

        if (!$user instanceof User) {
            return $this->redirectToRoute('app_security_login');
        }

        $token = $request->request->get('_token');
        $wait = $verification->secondsBeforeResend($user);

        match (true) {
            !$this->isCsrfTokenValid('verify_email_resend', is_string($token) ? $token : null) => $this->addFlash('error', $this->translate('settings.errors.csrf')),
            $user->isEmailVerified() => $this->addFlash('info', $this->translate('verify_email.flash.already_verified')),
            $wait > 0 => $this->addFlash('warning', $this->translate('verify_email.flash.wait', ['minutes' => (int) ceil($wait / 60)])),
            default => $this->sendVerification($verification, $user),
        };

        return $this->redirectToRoute('home_index');
    }

    #[Route('/logout', name: '_logout', methods: ['GET', 'POST'])]
    public function logout(): Response
    {
        throw new SecurityException('This route is handled by the "logout" option of the firewall in config/packages/security.yaml.');
    }

    private function sendVerification(EmailVerificationHelper $verification, User $user): void
    {
        try {
            $verification->send($user);
            $this->addFlash('success', $this->translate('verify_email.flash.sent', ['email' => $user->getEmail()]));
        } catch (\Throwable) {
            $this->addFlash('error', $this->translate('verify_email.flash.send_failed'));
        }
    }
}