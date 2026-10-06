<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\WebsiteContactObject;
use NeoPHP\Package\Orm\Contract\AbstractRepository;

class WebsiteContactObjectRepository extends AbstractRepository
{
    protected string $entityClass = WebsiteContactObject::class;

    public function findAllOrdered(): array
    {
        return $this->findBy([], ['id' => 'ASC']);
    }
}
