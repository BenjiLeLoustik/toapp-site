<?php

declare(strict_types=1);

namespace App\User\Helper;

use App\Email\VerifyEmail;
use App\Entity\User;
use App\User\Enum\EmailVerificationResultEnum;
use NeoPHP\Component\Container\Attribute\Autowire;
use NeoPHP\Component\Mailer\MailerManagerInterface;
use NeoPHP\Component\Routing\RoutingManagerInterface;
use NeoPHP\Component\View\ViewManagerInterface;
use NeoPHP\Package\Orm\Contract\EntityManagerInterface;
use NeoPHP\Package\Translation\TranslationManagerInterface;

class EmailVerificationHelper
{
    public const LIFETIME = 172800;

    public const RESEND_DELAY = 300;

    public function __construct(
        private EntityManagerInterface $entityManager,
        private MailerManagerInterface $mailer,
        private RoutingManagerInterface $routing,
        private ViewManagerInterface $view,
        private TranslationManagerInterface $translator,
        #[Autowire(env: 'APP_SECRET')]
        private string $secret,
        #[Autowire(env: 'APP_NAME')]
        private string $appName,
    ) {
    }

    public function send(User $user): bool
    {
        if ($user->isEmailVerified() || $user->getEmail() === null) {
            return false;
        }

        $expires = time() + self::LIFETIME;

        $url = $this->routing->generate('app_security_verify_email', [
            'id' => $user->getId(),
            'expires' => $expires,
            'signature' => $this->signature($user, $expires),
        ], true);

        $html = $this->view->render('emails/verify_email.html.twig', [
            'name' => $user->getFirstname(),
            'url' => $url,
            'app' => $this->appName,
            'hours' => intdiv(self::LIFETIME, 3600),
        ]);

        $this->mailer->send(new VerifyEmail(
            $user->getEmail(),
            $this->translator->translate('emails.verify.subject', ['app' => $this->appName]),
            $html
        ));

        $user->setVerificationSentAt(new \DateTime());
        $this->entityManager->flush();

        return true;
    }

    public function secondsBeforeResend(User $user): int
    {
        $sentAt = $user->getVerificationSentAt();

        if ($sentAt === null) {
            return 0;
        }

        return max(0, $sentAt->getTimestamp() + self::RESEND_DELAY - time());
    }

    public function verify(int $id, int $expires, string $signature): EmailVerificationResultEnum
    {
        $user = $this->entityManager->getRepository(User::class)->find($id);

        if (!$user instanceof User || $signature === '' || !hash_equals($this->signature($user, $expires), $signature)) {
            return EmailVerificationResultEnum::INVALID;
        }

        if ($expires < time()) {
            return EmailVerificationResultEnum::EXPIRED;
        }

        if ($user->isEmailVerified()) {
            return EmailVerificationResultEnum::ALREADY_VERIFIED;
        }

        $user->setEmailVerifiedAt(new \DateTime());
        $this->entityManager->flush();

        return EmailVerificationResultEnum::VERIFIED;
    }

    public function reset(User $user): void
    {
        $user
            ->setEmailVerifiedAt(null)
            ->setVerificationSentAt(null);
    }

    private function signature(User $user, int $expires): string
    {
        return hash_hmac('sha256', implode('|', [$user->getId(), strtolower((string) $user->getEmail()), $expires]), $this->secret);
    }
}