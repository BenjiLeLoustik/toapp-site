<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Project;
use App\Entity\ProjectView;
use App\Entity\User;
use NeoPHP\Package\Orm\Contract\AbstractRepository;

class ProjectViewRepository extends AbstractRepository
{
    protected string $entityClass = ProjectView::class;

    public function existsForUser(Project $project, User $user): bool
    {
        return $this->findOneBy([
            'project' => $project,
            'user' => $user
        ]) !== null;
    }
}
