<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\CategoryTranslated;
use NeoPHP\Package\Orm\Contract\AbstractRepository;

class CategoryTranslatedRepository extends AbstractRepository
{
    protected string $entityClass = CategoryTranslated::class;
}
