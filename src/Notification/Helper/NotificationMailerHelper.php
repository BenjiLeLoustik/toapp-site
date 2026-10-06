<?php

declare(strict_types=1);

namespace App\Notification\Helper;

use App\Email\NotificationEmail;
use App\Entity\Project;
use App\Entity\User;
use App\Notification\Enum\NotificationTypeEnum;
use App\User\Helper\UserSettingsHelper;
use NeoPHP\Component\Container\Attribute\Autowire;
use NeoPHP\Component\Mailer\Contract\MailerInterface;
use NeoPHP\Component\Routing\Contract\RoutingInterface;
use NeoPHP\Component\View\Contract\ViewInterface;
use NeoPHP\Package\Orm\Contract\EntityManagerInterface;
use NeoPHP\Package\Translation\Contract\TranslatorInterface;

class NotificationMailerHelper
{
    public const PROJECT_ROUTE = 'project_show';

    public const UNSUBSCRIBE_ROUTE = 'app_notification_unsubscribe';

    public function __construct(
        private EntityManagerInterface $entityManager,
        private MailerInterface $mailer,
        private RoutingInterface $routing,
        private ViewInterface $view,
        private TranslatorInterface $translator,
        private UserSettingsHelper $settings,
        #[Autowire(env: 'APP_SECRET')]
        private string $secret,
        #[Autowire(env: 'APP_NAME')]
        private string $appName,
    ) {
    }

    public function canReceive(User $user): bool
    {
        return $user->getEmail() !== null
            && $user->isEmailVerified()
            && !$user->isDeactivated()
            && !$user->isDeleted();
    }

    public function send(User $user, NotificationTypeEnum $type, string $subjectKey, string $template, array $params = []): void
    {
        $html = $this->view->render($template, [
            ...$params,
            'app' => $this->appName,
            'name' => $user->getFirstname(),
            'unsubscribe_url' => $this->unsubscribeUrl($user, $type),
        ]);

        $this->mailer->send(new NotificationEmail(
            (string) $user->getEmail(),
            $this->translator->translate($subjectKey, [...($params['subject'] ?? []), 'app' => $this->appName]),
            $html
        ));
    }

    public function projectUrl(Project $project): string
    {
        return $this->routing->generate(self::PROJECT_ROUTE, [
            'id' => $project->getId(),
            'slug' => $project->getSlug(),
        ], true);
    }

    public function unsubscribeUrl(User $user, NotificationTypeEnum $type): string
    {
        return $this->routing->generate(self::UNSUBSCRIBE_ROUTE, [
            'id' => $user->getId(),
            'type' => $type->value,
            'signature' => $this->signature($user, $type),
        ], true);
    }

    public function unsubscribe(int $id, string $type, string $signature): ?NotificationTypeEnum
    {
        $type = NotificationTypeEnum::tryFrom($type);
        $user = $this->entityManager->getRepository(User::class)->find($id);

        if ($type === null || !$user instanceof User || $signature === '' || !hash_equals($this->signature($user, $type), $signature)) {
            return null;
        }

        $type->unsubscribe($this->settings->getNotifications($user));
        $this->entityManager->flush();

        return $type;
    }

    private function signature(User $user, NotificationTypeEnum $type): string
    {
        return hash_hmac('sha256', implode('|', ['unsubscribe', $user->getId(), strtolower((string) $user->getEmail()), $type->value]), $this->secret);
    }
}