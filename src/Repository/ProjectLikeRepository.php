<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\ProjectLike;
use NeoPHP\Package\Orm\Contract\AbstractRepository;

class ProjectLikeRepository extends AbstractRepository
{
    protected string $entityClass = ProjectLike::class;
}
