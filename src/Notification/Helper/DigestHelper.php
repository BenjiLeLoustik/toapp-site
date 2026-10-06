<?php

declare(strict_types=1);

namespace App\Notification\Helper;

use App\Entity\Project;
use App\Entity\User;
use App\Entity\UserFollow;
use App\Entity\UserNotificationSetting;
use App\Notification\Enum\NotificationTypeEnum;
use App\Project\Helper\ProjectStatsHelper;
use NeoPHP\Component\Logger\Contract\LoggerInterface;
use NeoPHP\Package\Orm\Contract\EntityManagerInterface;

class DigestHelper
{
    public const FOLLOWERS_LIMIT = 10;

    public function __construct(
        private EntityManagerInterface $entityManager,
        private NotificationMailerHelper $mailer,
        private LoggerInterface $logger,
    ) {
    }

    public function run(bool $dryRun = false, bool $force = false): array
    {
        $now = new \DateTime();
        $current = \DateTimeImmutable::createFromMutable($now);
        $report = ['checked' => 0, 'sent' => 0, 'failed' => 0];

        foreach ($this->entityManager->getRepository(UserNotificationSetting::class)->findAll() as $setting) {
            $user = $setting->getUser();
            $frequency = $setting->getDigestFrequency();

            if (!$user instanceof User || $frequency->interval() === null) {
                continue;
            }

            if (!$force && !$frequency->isDue($setting->getDigestSentAt(), $current)) {
                continue;
            }

            $report['checked']++;
            $digest = $this->build($user, $frequency->since($setting->getDigestSentAt(), $current), $current);

            if (!$dryRun) {
                $setting->setDigestSentAt(clone $now);
            }

            if ($digest['total'] === 0 || !$this->mailer->canReceive($user)) {
                continue;
            }

            if ($dryRun) {
                $report['sent']++;
                continue;
            }

            try {
                $this->mailer->send($user, NotificationTypeEnum::DIGEST, 'emails.digest.subject', 'emails/digest.html.twig', [
                    ...$digest,
                    'frequency' => $frequency->value,
                ]);

                $report['sent']++;
            } catch (\Throwable $exception) {
                $report['failed']++;

                $this->logger->error('The digest email of the user #{id} could not be sent: {message}', [
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

    public function build(User $user, \DateTimeImmutable $since, \DateTimeImmutable $until): array
    {
        $totals = array_fill_keys(array_keys(ProjectStatsHelper::CARDS), 0);
        $projects = [];

        foreach ($user->getProjects() as $project) {
            $counts = [];

            foreach (ProjectStatsHelper::CARDS as $key => $class) {
                $counts[$key] = $this->count($class, $project, $since, $until);
                $totals[$key] += $counts[$key];
            }

            if (array_sum($counts) > 0) {
                $projects[] = [
                    'name' => $project->getName(),
                    'url' => $this->mailer->projectUrl($project),
                    ...$counts,
                ];
            }
        }

        usort($projects, static fn (array $a, array $b): int => $b['views'] <=> $a['views']);

        $followers = $this->followers($user, $since, $until);

        return [
            'totals' => $totals,
            'total' => array_sum($totals) + $followers['count'],
            'projects' => $projects,
            'top' => $projects[0] ?? null,
            'followers' => $followers,
        ];
    }

    private function followers(User $user, \DateTimeImmutable $since, \DateTimeImmutable $until): array
    {
        $follows = $this->entityManager->getRepository(UserFollow::class)->createQueryBuilder('x')
            ->where('x.followed = :user')
            ->andWhere('x.createdAt >= :from')
            ->andWhere('x.createdAt < :to')
            ->setParameter('user', $user->getId())
            ->setParameter('from', $since->format('Y-m-d H:i:s'))
            ->setParameter('to', $until->format('Y-m-d H:i:s'))
            ->orderBy('x.createdAt', 'DESC')
            ->getResult();

        $names = [];

        foreach ($follows as $follow) {
            $follower = $follow->getFollower();

            if ($follower instanceof User && !$follower->isDeleted() && !$follower->isDeactivated()) {
                $names[] = $follower->getUsername();
            }
        }

        return [
            'count' => count($names),
            'names' => array_slice($names, 0, self::FOLLOWERS_LIMIT),
            'others' => max(0, count($names) - self::FOLLOWERS_LIMIT),
        ];
    }

    private function count(string $class, Project $project, \DateTimeImmutable $since, \DateTimeImmutable $until): int
    {
        return (int) $this->entityManager->getRepository($class)->createQueryBuilder('x')
            ->select('COUNT(x.id) AS total')
            ->where('x.project = :project')
            ->andWhere('x.createdAt >= :from')
            ->andWhere('x.createdAt < :to')
            ->setParameter('project', $project->getId())
            ->setParameter('from', $since->format('Y-m-d H:i:s'))
            ->setParameter('to', $until->format('Y-m-d H:i:s'))
            ->getSingleScalarResult();
    }
}