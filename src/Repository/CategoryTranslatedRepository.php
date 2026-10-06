<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\CategoryTranslated;
use App\Repository\Contract\AbstractTranslatedRepository;

class CategoryTranslatedRepository extends AbstractTranslatedRepository
{
    protected string $entityClass = CategoryTranslated::class;
}
