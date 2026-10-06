<?php

declare(strict_types=1);

namespace App\Project\Helper;

use App\Entity\Project;
use App\Entity\ProjectFavorite;
use App\Entity\ProjectLike;
use App\Entity\ProjectShare;
use App\Entity\ProjectView;
use App\Project\Enum\ProjectShareEnum;
use App\Project\Enum\ProjectStatsPeriodEnum;
use App\Project\Enum\ProjectViewSourceEnum;
use App\User\Helper\UserFormatHelper;
use NeoPHP\Package\Orm\Contract\EntityManagerInterface;
use NeoPHP\Package\Orm\Query\QueryBuilder;

class ProjectStatsHelper
{
    public const ACTIVITY_PER_PAGE = 5;

    public const ACTIVITY_LIMIT = 50;

    public const COUNTRIES_LIMIT = 5;

    public const CARDS = [
        'views' => ProjectView::class,
        'likes' => ProjectLike::class,
        'shares' => ProjectShare::class,
        'favorites' => ProjectFavorite::class,
    ];

    public function __construct(
        private EntityManagerInterface $entityManager,
        private UserFormatHelper $format,
    ) {
    }

    public function build(Project $project, ProjectStatsPeriodEnum $period, int $activityPage = 1): array
    {
        $now = new \DateTimeImmutable();
        $since = $period->since($now);
        $previousSince = $period->previousSince($now);

        $cards = [];

        foreach (self::CARDS as $key => $class) {
            $current = $this->count($class, $project, $since, null);
            $previous = $since !== null ? $this->count($class, $project, $previousSince, $since) : null;

            $cards[$key] = ['value' => $current, 'change' => $this->change($current, $previous)];
        }

        return [
            'cards' => $cards,
            'series' => $this->viewsSeries($project, $period, $since, $now),
            'sources' => $this->sources($project, $since),
            'countries' => $this->countries($project, $since, $cards['views']['value']),
            'networks' => $this->networks($project, $since),
            'activity' => $this->activity($project, $since, $activityPage),
        ];
    }

    private function query(string $class, Project $project, ?\DateTimeImmutable $from, ?\DateTimeImmutable $to): QueryBuilder
    {
        $queryBuilder = $this->entityManager->getRepository($class)->createQueryBuilder('x')
            ->where('x.project = :project')
            ->setParameter('project', $project->getId());

        if ($from !== null) {
            $queryBuilder->andWhere('x.createdAt >= :from')->setParameter('from', $from->format('Y-m-d H:i:s'));
        }

        if ($to !== null) {
            $queryBuilder->andWhere('x.createdAt < :to')->setParameter('to', $to->format('Y-m-d H:i:s'));
        }

        return $queryBuilder;
    }

    private function count(string $class, Project $project, ?\DateTimeImmutable $from, ?\DateTimeImmutable $to): int
    {
        return (int) $this->query($class, $project, $from, $to)
            ->select('COUNT(x.id) AS total')
            ->getSingleScalarResult();
    }

    private function change(int $current, ?int $previous): ?float
    {
        if ($previous === null) {
            return null;
        }

        if ($previous === 0) {
            return $current > 0 ? 100.0 : 0.0;
        }

        return round(($current - $previous) / $previous * 100, 1);
    }

    private function viewsSeries(Project $project, ProjectStatsPeriodEnum $period, ?\DateTimeImmutable $since, \DateTimeImmutable $now): array
    {
        $timezone = $this->format->timezone();
        $unit = $period->bucket();

        $rows = $this->query(ProjectView::class, $project, $since, null)
            ->select('x.createdAt AS created_at')
            ->getScalarResult();

        $start = $since ?? \DateTimeImmutable::createFromInterface($project->getCreatedAt());
        $cursor = $this->bucketStart($start->setTimezone($timezone), $unit);
        $end = $now->setTimezone($timezone);
        $buckets = [];

        while ($cursor <= $end) {
            $buckets[$this->bucketKey($cursor, $unit)] = [
                'label' => $cursor->format($unit === 'month' ? 'm/Y' : 'd/m'),
                'value' => 0,
            ];

            $cursor = $cursor->modify(match ($unit) {
                'day' => '+1 day',
                'week' => '+1 week',
                default => '+1 month',
            });
        }

        foreach ($rows as $row) {
            $key = $this->bucketKey((new \DateTimeImmutable((string) $row['created_at']))->setTimezone($timezone), $unit);

            if (isset($buckets[$key])) {
                $buckets[$key]['value']++;
            }
        }

        return array_values($buckets);
    }

    private function bucketStart(\DateTimeImmutable $date, string $unit): \DateTimeImmutable
    {
        return match ($unit) {
            'day' => $date->setTime(0, 0),
            'week' => $date->modify('monday this week')->setTime(0, 0),
            default => $date->modify('first day of this month')->setTime(0, 0),
        };
    }

    private function bucketKey(\DateTimeImmutable $date, string $unit): string
    {
        return $date->format(match ($unit) {
            'day' => 'Y-m-d',
            'week' => 'o-W',
            default => 'Y-m',
        });
    }

    private function sources(Project $project, ?\DateTimeImmutable $since): array
    {
        $totals = array_fill_keys(array_map(static fn (ProjectViewSourceEnum $source): string => $source->value, ProjectViewSourceEnum::cases()), 0);

        $rows = $this->query(ProjectView::class, $project, $since, null)
            ->select('x.source AS source', 'COUNT(x.id) AS total')
            ->groupBy('x.source')
            ->getScalarResult();

        foreach ($rows as $row) {
            $source = ProjectViewSourceEnum::tryFrom((string) ($row['source'] ?? '')) ?? ProjectViewSourceEnum::DIRECT;
            $totals[$source->value] += (int) $row['total'];
        }

        $sum = array_sum($totals);
        $segments = [];

        foreach ($totals as $key => $value) {
            $segments[] = [
                'key' => $key,
                'value' => $value,
                'percent' => $sum > 0 ? (int) round($value / $sum * 100) : 0,
            ];
        }

        return ['total' => $sum, 'segments' => $segments];
    }

    private function countries(Project $project, ?\DateTimeImmutable $since, int $totalViews): array
    {
        $rows = $this->query(ProjectView::class, $project, $since, null)
            ->select('x.country AS country', 'COUNT(x.id) AS total')
            ->andWhere('x.country IS NOT NULL')
            ->groupBy('x.country')
            ->orderBy('total', 'DESC')
            ->setMaxResults(self::COUNTRIES_LIMIT)
            ->getScalarResult();

        $countries = [];

        foreach ($rows as $row) {
            $code = strtoupper((string) $row['country']);
            $value = (int) $row['total'];

            $countries[] = [
                'code' => $code,
                'name' => $this->countryName($code),
                'flag' => $this->countryFlag($code),
                'value' => $value,
                'percent' => $totalViews > 0 ? (int) round($value / $totalViews * 100) : 0,
            ];
        }

        return $countries;
    }

    private function networks(Project $project, ?\DateTimeImmutable $since): array
    {
        $totals = array_fill_keys(array_map(static fn (ProjectShareEnum $type): string => $type->value, ProjectShareEnum::cases()), 0);

        $rows = $this->query(ProjectShare::class, $project, $since, null)
            ->select('x.type AS type', 'COUNT(x.id) AS total')
            ->groupBy('x.type')
            ->getScalarResult();

        foreach ($rows as $row) {
            if (isset($totals[(string) $row['type']])) {
                $totals[(string) $row['type']] = (int) $row['total'];
            }
        }

        arsort($totals);
        $max = max($totals) ?: 1;
        $networks = [];

        foreach ($totals as $key => $value) {
            $networks[] = [
                'key' => $key,
                'value' => $value,
                'width' => (int) round($value / $max * 100),
            ];
        }

        return $networks;
    }

    private function activity(Project $project, ?\DateTimeImmutable $since, int $page): array
    {
        $items = [];

        foreach (['like' => ProjectLike::class, 'favorite' => ProjectFavorite::class, 'share' => ProjectShare::class] as $type => $class) {
            $entities = $this->query($class, $project, $since, null)
                ->orderBy('x.createdAt', 'DESC')
                ->setMaxResults(self::ACTIVITY_LIMIT)
                ->getResult();

            foreach ($entities as $entity) {
                $items[] = [
                    'type' => $type,
                    'user' => $entity->getUser(),
                    'network' => $entity instanceof ProjectShare ? $entity->getType()->value : null,
                    'date' => $entity->getCreatedAt(),
                ];
            }
        }

        usort($items, static fn (array $a, array $b): int => $b['date'] <=> $a['date']);
        $items = array_slice($items, 0, self::ACTIVITY_LIMIT);

        $pages = max(1, (int) ceil(count($items) / self::ACTIVITY_PER_PAGE));
        $page = min(max(1, $page), $pages);

        return [
            'items' => array_slice($items, ($page - 1) * self::ACTIVITY_PER_PAGE, self::ACTIVITY_PER_PAGE),
            'page' => $page,
            'pages' => $pages,
        ];
    }

    private function countryName(string $code): string
    {
        if (class_exists(\Locale::class)) {
            $name = \Locale::getDisplayRegion('und-' . $code, 'en');

            if ($name !== '' && $name !== $code) {
                return $name;
            }
        }

        return $code;
    }

    private function countryFlag(string $code): string
    {
        if (preg_match('/^[A-Z]{2}$/', $code) !== 1) {
            return '';
        }

        return mb_chr(0x1F1E6 + ord($code[0]) - 65) . mb_chr(0x1F1E6 + ord($code[1]) - 65);
    }
}