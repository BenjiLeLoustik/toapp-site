<?php

declare(strict_types=1);

namespace App\User\Listener;

use App\Entity\User;
use App\Entity\UserLoginHistory;
use App\User\Helper\UserSettingsHelper;
use NeoPHP\Component\Container\Attribute\Autowire;
use NeoPHP\Component\Event\Attribute\AsListener;
use NeoPHP\Component\Session\SessionManagerInterface;
use NeoPHP\Package\Orm\Contract\EntityManagerInterface;
use NeoPHP\Package\Security\Event\LoginSuccessEvent;

#[AsListener]
class LoginSuccessListener
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private UserSettingsHelper $settings,
        private SessionManagerInterface $session,
        #[Autowire(env: 'APP_SESSION_THEME')]
        private string $sessionTheme,
        #[Autowire(env: 'APP_SESSION_ACCENT')]
        private string $sessionAccent,
        #[Autowire(env: 'APP_SESSION_SCALE')]
        private string $sessionScale,
    ) {
    }

    public function __invoke(LoginSuccessEvent $event): void
    {
        $user = $event->getUser();

        if (!$user instanceof User) {
            return;
        }

        $request = $event->getRequest();

        $history = new UserLoginHistory();
        $history
            ->setUser($user)
            ->setIp($request?->getClientIp())
            ->setUserAgent($request?->headers->get('User-Agent'))
            ->setMethod((string) ($event->getAuthenticator() ?? 'form'));

        $user->setLastLoginAt(new \DateTime());

        if ($user->isDeactivated()) {
            $user->setDeactivatedAt(null);
        }

        $this->entityManager->persist($history);
        $this->entityManager->flush();

        $preference = $this->settings->getPreference($user);

        foreach ([$this->sessionTheme => $preference->getTheme(), $this->sessionAccent => $preference->getAccent(), $this->sessionScale => $preference->getScale()] as $key => $value) {
            if ($value !== null) {
                $this->session->set($key, $value);
            }
        }
    }
}