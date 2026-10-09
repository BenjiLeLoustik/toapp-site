<?php

declare(strict_types=1);

namespace App\Notification\Helper;

use App\Entity\Project;
use App\Entity\ProjectFavorite;
use App\Entity\ProjectLike;
use App\Entity\ProjectShare;
use App\Entity\User;
use App\Entity\UserNotificationSetting;
use App\Notification\Enum\NotificationTypeEnum;
use NeoPHP\Component\Logger\LoggerManagerInterface;
use NeoPHP\Package\Orm\Contract\EntityManagerInterface;

class ActivityNotificationHelper
{
    public const WINDOW = '-1 hour';

    public const SOURCES = [
        'likes' => ProjectLike::class,
        'shares' => ProjectShare::class,
        'favorites' => ProjectFavorite::class,
    ];

    public function __construct(
        private EntityManagerInterface $entityManager,
        private NotificationMailerHelper $mailer,
        private LoggerManagerInterface $logger,
    ) {
    }

    public function run(bool $dryRun = false): array
    {
        $now = new \DateTime();
        $report = ['checked' => 0, 'sent' => 0, 'failed' => 0];

        foreach ($this->entityManager->getRepository(UserNotificationSetting::class)->findAll() as $setting) {
            $user = $setting->getUser();
            $sources = $this->sources($setting);

            if (!$user instanceof User || $sources === []) {
                continue;
            }

            $report['checked']++;
            $since = $setting->getActivityNotifiedAt() ?? (clone $now)->modify(self::WINDOW);
            $projects = $this->collect($user, $sources, $since, $now);

            if (!$dryRun) {
                $setting->setActivityNotifiedAt(clone $now);
            }

            if ($projects === [] || !$this->mailer->canReceive($user)) {
                continue;
            }

            if ($dryRun) {
                $report['sent']++;
                continue;
            }

            try {
                $this->mailer->send($user, NotificationTypeEnum::ACTIVITY, 'emails.activity.subject', 'emails/activity.html.twig', [
                    'projects' => $projects,
                    'total' => array_sum(array_column($projects, 'total')),
                ]);

                $report['sent']++;
            } catch (\Throwable $exception) {
                $report['failed']++;

                $this->logger->error('The activity email of the user #{id} could not be sent: {message}', [
                    'id' => $user->getId(),
                    'message' => $exception->getMessage(),
                ]);
            }
        }

        if (!$dryRun) {
            $this->entityManager->flush();
        }

        return $report;
    }

    private function sources(UserNotificationSetting $setting): array
    {
        return array_keys(array_filter([
            'likes' => $setting->isEmailProjectLikes(),
            'shares' => $setting->isEmailProjectLikes(),
            'favorites' => $setting->isEmailProjectFavorites(),
        ]));
    }

    private function collect(User $owner, array $sources, \DateTimeInterface $since, \DateTimeInterface $until): array
    {
        $projects = [];

        foreach ($owner->getProjects() as $project) {
            $group = $this->group($project, $owner, $sources, $since, $until);

            if ($group['total'] > 0) {
                $projects[] = $group;
            }
        }

        return $projects;
    }

    private function group(Project $project, User $owner, array $sources, \DateTimeInterface $since, \DateTimeInterface $until): array
    {
        $group = [
            'name' => $project->getName(),
            'url' => $this->mailer->projectUrl($project),
            'likes' => [],
            'favorites' => [],
            'shares' => [],
            'anonymous' => 0,
            'total' => 0,
        ];

        foreach ($sources as $source) {
            $entities = $this->entityManager->getRepository(self::SOURCES[$source])->createQueryBuilder('x')
                ->where('x.project = :project')
                ->andWhere('x.createdAt >= :from')
                ->andWhere('x.createdAt < :to')
                ->setParameter('project', $project->getId())
                ->setParameter('from', $since->format('Y-m-d H:i:s'))
                ->setParameter('to', $until->format('Y-m-d H:i:s'))
                ->orderBy('x.createdAt', 'DESC')
                ->getResult();

            foreach ($entities as $entity) {
                $actor = $entity->getUser();

                if ($actor instanceof User && $actor->getId() === $owner->getId()) {
                    continue;
                }

                $group['total']++;

                if ($entity instanceof ProjectShare) {
                    $actor instanceof User
                        ? $group['shares'][] = ['user' => $actor->getUsername(), 'network' => ucfirst($entity->getType()->value)]
                        : $group['anonymous']++;

                    continue;
                }

                if ($actor instanceof User) {
                    $group[$source][] = $actor->getUsername();
                }
            }
        }

        return $group;
    }
}