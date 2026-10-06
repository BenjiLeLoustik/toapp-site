<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\ProjectFavorite;
use NeoPHP\Package\Orm\Contract\AbstractRepository;

class ProjectFavoriteRepository extends AbstractRepository
{
    protected string $entityClass = ProjectFavorite::class;
}
