<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\ProjectView;
use NeoPHP\Package\Orm\Contract\AbstractRepository;

class ProjectViewRepository extends AbstractRepository
{
    protected string $entityClass = ProjectView::class;
}
