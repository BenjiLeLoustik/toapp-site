<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Project;
use NeoPHP\Package\Orm\Contract\AbstractRepository;

class ProjectRepository extends AbstractRepository
{
    protected string $entityClass = Project::class;

    public function findPopular(int $limit): array
    {
        return $this->findBy([], ['createdAt' => 'ASC'], $limit);
    }
}
