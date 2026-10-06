<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\ProjectShare;
use NeoPHP\Package\Orm\Contract\AbstractRepository;

class ProjectShareRepository extends AbstractRepository
{
    protected string $entityClass = ProjectShare::class;
}
