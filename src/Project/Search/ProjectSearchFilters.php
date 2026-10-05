<?php

namespace App\Project\Search;

use App\Project\Enum\ProjectDateEnum;
use App\Project\Enum\ProjectSortEnum;

class ProjectSearchFilters
{
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