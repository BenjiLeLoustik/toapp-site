<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Technology;
use NeoPHP\Package\Orm\Contract\AbstractRepository;

class TechnologyRepository extends AbstractRepository
{
    protected string $entityClass = Technology::class;
}
