<?php

namespace App\Project\Search;

use App\Project\Enum\ProjectDateEnum;
use App\Project\Enum\ProjectSortEnum;

class ProjectSearchFilters
{
    public const ALL_CATEGORIES = 'all';

    public function __construct(
        private string $search = '',
        private array $technologies = [],
        private ProjectSortEnum $sort = ProjectSortEnum::RELEVANCE,
        private ProjectDateEnum $date = ProjectDateEnum::ALL,
    ) {
    }

    public static function fromQuery(array $query): self
    {
        return new self(
            trim(is_string($query['search'] ?? null) ? $query['search'] : ''),
            array_values(array_filter((array) ($query['technologies'] ?? []), 'is_string')),
            ProjectSortEnum::tryFrom((string) ($query['sort'] ?? '')) ?? ProjectSortEnum::RELEVANCE,
            ProjectDateEnum::tryFrom((string) ($query['date'] ?? '')) ?? ProjectDateEnum::ALL,
        );
    }

    public static function categoryRedirect(array $query, ?string $currentSlug): ?array
    {
        $selected = $query['category'] ?? ($currentSlug ?? self::ALL_CATEGORIES);

        if (!is_string($selected) || $selected === '') {
            $selected = self::ALL_CATEGORIES;
        }

        if ($selected === ($currentSlug ?? self::ALL_CATEGORIES)) {
            return null;
        }

        unset($query['category'], $query['page']);

        if ($selected === self::ALL_CATEGORIES) {
            return ['project_index', $query];
        }

        return ['category_show', ['slug' => $selected] + $query];
    }

    public function getSearch(): string
    {
        return $this->search;
    }

    public function hasSearch(): bool
    {
        return $this->search !== '';
    }

    public function getTechnologies(): array
    {
        return $this->technologies;
    }

    public function hasTechnology(string $slug): bool
    {
        return in_array($slug, $this->technologies, true);
    }

    public function getSort(): ProjectSortEnum
    {
        return $this->sort;
    }

    public function getDate(): ProjectDateEnum
    {
        return $this->date;
    }
}