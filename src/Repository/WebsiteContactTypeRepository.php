<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\WebsiteContactType;
use NeoPHP\Package\Orm\Contract\AbstractRepository;

class WebsiteContactTypeRepository extends AbstractRepository
{
    protected string $entityClass = WebsiteContactType::class;
}
