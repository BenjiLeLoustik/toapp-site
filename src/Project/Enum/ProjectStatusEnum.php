<?php

declare(strict_types=1);

namespace App\Project\Enum;

use NeoPHP\Package\Orm\Query\QueryBuilder;

enum ProjectStatusEnum: string
{
    case ALL = 'all';
    case PUBLISHED = 'published';
    case DRAFT = 'draft';
    case ARCHIVED = 'archived';

    public static function editable(): array
    {
        return [self::DRAFT, self::PUBLISHED, self::ARCHIVED];
    }

    public function apply(QueryBuilder $queryBuilder, string $alias = 'p'): QueryBuilder
    {
        return match ($this) {
            self::ALL => $queryBuilder,
            self::PUBLISHED => $queryBuilder->andWhere($alias . '.publishedAt IS NOT NULL')->andWhere($alias . '.archivedAt IS NULL'),
            self::DRAFT => $queryBuilder->andWhere($alias . '.publishedAt IS NULL')->andWhere($alias . '.archivedAt IS NULL'),
            self::ARCHIVED => $queryBuilder->andWhere($alias . '.archivedAt IS NOT NULL'),
        };
    }
}