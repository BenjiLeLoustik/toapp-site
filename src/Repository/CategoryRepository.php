<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Category;
use NeoPHP\Package\Orm\Contract\AbstractRepository;

class CategoryRepository extends AbstractRepository
{
    protected string $entityClass = Category::class;

}
