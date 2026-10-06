<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\User;
use App\Entity\UserLoginHistory;
use App\User\Enum\UserDateFormatEnum;
use App\User\Enum\UserDigestFrequencyEnum;
use App\User\Enum\UserNumberFormatEnum;
use App\User\Helper\UserSettingsHelper;
use NeoPHP\Component\Container\Attribute\Autowire;
use NeoPHP\Component\Controller\Contract\AbstractController;
use NeoPHP\Component\Http\Request\Request;
use NeoPHP\Component\Http\Response\Response;
use NeoPHP\Component\Routing\Attribute\Route;
use NeoPHP\Package\Orm\Contract\EntityManagerInterface;

#[Route(path: '/settings', name: 'settings')]
class SettingsController extends AbstractController
{
    public function __construct(
        protected EntityManagerInterface $entityManager,
        protected UserSettingsHelper $settings,
        #[Autowire(env: 'APP_DESIGN_THEMES')]
        protected string $availableThemes,
        #[Autowire(env: 'APP_DESIGN_ACCENTS')]
        protected string $availableAccents,
        #[Autowire(env: 'APP_DESIGN_SCALES')]
        protected string $availableScales,
        #[Autowire(env: 'APP_SESSION_THEME')]
        protected string $sessionTheme,
        #[Autowire(env: 'APP_SESSION_ACCENT')]
        protected string $sessionAccent,
        #[Autowire(env: 'APP_SESSION_SCALE')]
        protected string $sessionScale,
    ) {
    }

    #[Route('', name: '_index', methods: ['GET'])]
    public function index(): Response
    {
        return $this->redirectToRoute('settings_profile');
    }

    #[Route('/profile', name: '_profile', methods: ['GET', 'POST'])]
    public function profile(Request $request): Response
    {
        $user = $this->currentUser();
        $errors = [];

        if ($request->isMethod('POST')) {
            $errors = $this->validCsrf($request, 'settings_profile')
                ?? $this->settings->updateProfile(
                    $user,
                    $request->request->all(),
                    $request->files->get('avatar'),
                    $request->request->get('remove_avatar') === '1'
                );

            if ($errors === []) {
                return $this->saved('settings_profile');
            }
        }

        return $this->render('pages/settings/profile.html.twig', [
            'user' => $user,
            'errors' => $errors,
            'values' => $request->isMethod('POST') ? $request->request->all() : [],
        ]);
    }

    #[Route('/account', name: '_account', methods: ['GET', 'POST'])]
    public function account(Request $request): Response
    {
        $user = $this->currentUser();
        $errors = [];

        if ($request->isMethod('POST')) {
            $errors = $this->validCsrf($request, 'settings_account') ?? $this->settings->updateEmail($user, $request->request->all());

            if ($errors === []) {
                return $this->saved('settings_account');
            }
        }

        return $this->render('pages/settings/account.html.twig', [
            'user' => $user,
            'errors' => $errors,
            'lastLogin' => $this->entityManager->getRepository(UserLoginHistory::class)->findPreviousForUser($user),
        ]);
    }

    #[Route('/account/deactivate', name: '_deactivate', methods: ['POST'])]
    public function deactivate(Request $request): Response
    {
        $user = $this->currentUser();

        if ($this->validCsrf($request, 'settings_deactivate') !== null) {
            return $this->redirectToRoute('settings_account');
        }

        $this->settings->deactivate($user);

        return $this->logoutUser();
    }

    #[Route('/account/delete', name: '_delete', methods: ['POST'])]
    public function delete(Request $request): Response
    {
        $user = $this->currentUser();

        if ($this->validCsrf($request, 'settings_delete') !== null
            || !$this->settings->checkPassword($user, (string) $request->request->get('delete_password', ''))) {
            $this->addFlash('error', $this->translate('settings.flash.delete_failed'));

            return $this->redirectToRoute('settings_account');
        }

        $this->settings->anonymize($user);

        return $this->logoutUser();
    }

    #[Route('/appearance', name: '_appearance', methods: ['GET', 'POST'])]
    public function appearance(Request $request): Response
    {
        $user = $this->currentUser();
        $themes = (array) json_decode($this->availableThemes, true);
        $accents = (array) json_decode($this->availableAccents, true);
        $scales = (array) json_decode($this->availableScales, true);

        if ($request->isMethod('POST')) {
            if ($this->validCsrf($request, 'settings_appearance') === null) {
                $locales = array_map('strval', (array) $request->attributes->get('_locales', []));
                $preference = $this->settings->updatePreference($user, $request->request->all(), $themes, $accents, $scales, $this->availableLocales($request));

                foreach ([$this->sessionTheme => $preference->getTheme(), $this->sessionAccent => $preference->getAccent(), $this->sessionScale => $preference->getScale()] as $key => $value) {
                    if ($value !== null) {
                        $this->getSession()->set($key, $value);
                    }
                }

                if ($preference->getLocale() !== null) {
                    $this->switchLocale($preference->getLocale());
                }

                return $this->saved('settings_appearance');
            }
        }

        return $this->render('pages/settings/appearance.html.twig', [
            'preference' => $this->settings->getPreference($user),
            'themes' => $themes,
            'accents' => $accents,
            'scales' => $scales,
            'dateFormats' => UserDateFormatEnum::cases(),
            'numberFormats' => UserNumberFormatEnum::cases(),
            'timezones' => \DateTimeZone::listIdentifiers(),
        ]);
    }

    #[Route('/notifications', name: '_notifications', methods: ['GET', 'POST'])]
    public function notifications(Request $request): Response
    {
        $user = $this->currentUser();

        if ($request->isMethod('POST') && $this->validCsrf($request, 'settings_notifications') === null) {
            $this->settings->updateNotifications($user, $request->request->all());

            return $this->saved('settings_notifications');
        }

        return $this->render('pages/settings/notifications.html.twig', [
            'notifications' => $this->settings->getNotifications($user),
            'frequencies' => UserDigestFrequencyEnum::cases(),
        ]);
    }

    #[Route('/privacy', name: '_privacy', methods: ['GET', 'POST'])]
    public function privacy(Request $request): Response
    {
        $user = $this->currentUser();

        if ($request->isMethod('POST') && $this->validCsrf($request, 'settings_privacy') === null) {
            $this->settings->updatePrivacy($user, $request->request->all());

            return $this->saved('settings_privacy');
        }

        return $this->render('pages/settings/privacy.html.twig', [
            'privacy' => $this->settings->getPrivacy($user),
        ]);
    }

    #[Route('/security', name: '_security', methods: ['GET', 'POST'])]
    public function security(Request $request): Response
    {
        $user = $this->currentUser();
        $errors = [];

        if ($request->isMethod('POST')) {
            $errors = $this->validCsrf($request, 'settings_security') ?? $this->settings->updatePassword($user, $request->request->all());

            if ($errors === []) {
                return $this->saved('settings_security');
            }
        }

        return $this->render('pages/settings/security.html.twig', [
            'errors' => $errors,
            'history' => $this->entityManager->getRepository(UserLoginHistory::class)->findRecentForUser($user, 10),
        ]);
    }

    protected function currentUser(): User
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        $user = $this->getUser();

        if (!$user instanceof User) {
            throw $this->createAccessDeniedException();
        }

        return $user;
    }

    protected function validCsrf(Request $request, string $id): ?array
    {
        $token = $request->request->get('_token');

        if ($this->isCsrfTokenValid($id, is_string($token) ? $token : null)) {
            return null;
        }

        return ['_form' => $this->translate('settings.errors.csrf')];
    }

    protected function saved(string $route): Response
    {
        $this->addFlash('success', $this->translate('settings.flash.saved'));

        return $this->redirectToRoute($route);
    }

    protected function availableLocales(Request $request): array
    {
        return $this->get('translator')->getLocales();
    }
}