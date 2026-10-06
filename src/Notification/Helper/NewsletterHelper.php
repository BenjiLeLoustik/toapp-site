<?php

declare(strict_types=1);

namespace App\Notification\Helper;

use App\Entity\User;
use App\Entity\UserNotificationSetting;
use App\Notification\Enum\NotificationTypeEnum;
use NeoPHP\Component\Logger\Contract\LoggerInterface;
use NeoPHP\Package\Orm\Contract\EntityManagerInterface;

class NewsletterHelper
{
    public const TEMPLATE_DIRECTORY = 'emails/newsletter/';

    public function __construct(
        private EntityManagerInterface $entityManager,
        private NotificationMailerHelper $mailer,
        private LoggerInterface $logger,
    ) {
    }

    public function isValidTemplate(string $template): bool
    {
        return str_starts_with($template, self::TEMPLATE_DIRECTORY)
            && str_ends_with($template, '.html.twig')
            && !str_contains($template, '..');
    }

    public function recipients(?string $email = null): array
    {
        if ($email !== null) {
            $user = $this->entityManager->getRepository(User::class)->findOneBy(['email' => strtolower(trim($email))]);

            return $user instanceof User ? [$user] : [];
        }

        $users = [];

        foreach ($this->entityManager->getRepository(UserNotificationSetting::class)->findBy(['emailNewsletter' => true]) as $setting) {
            $user = $setting->getUser();

            if ($user instanceof User && $this->mailer->canReceive($user)) {
                $users[] = $user;
            }
        }

        return $users;
    }

    public function send(array $users, string $subject, string $template, bool $dryRun = false): array
    {
        $report = ['checked' => count($users), 'sent' => 0, 'failed' => 0];

        foreach ($users as $user) {
            if ($dryRun) {
                $report['sent']++;
                continue;
            }

            try {
                $this->mailer->send($user, NotificationTypeEnum::NEWSLETTER, 'emails.newsletter.subject', $template, [
                    'subject' => ['subject' => $subject],
                    'title' => $subject,
                ]);

                $report['sent']++;
            } catch (\Throwable $exception) {
                $report['failed']++;

                $this->logger->error('The newsletter could not be sent to the user #{id}: {message}', [
                    'id' => $user->getId(),
                    'message' => $exception->getMessage(),
                ]);
            }
        }

        return $report;
    }
}