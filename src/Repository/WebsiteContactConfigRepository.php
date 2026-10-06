<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\WebsiteContactConfig;
use NeoPHP\Package\Orm\Contract\AbstractRepository;

class WebsiteContactConfigRepository extends AbstractRepository
{
    protected string $entityClass = WebsiteContactConfig::class;

    public function findEnabled(): array
    {
        return $this->createQueryBuilder('c')
            ->innerJoin('c.type', 't')
            ->where('c.enabled = 1')
            ->orderBy('c.id', 'ASC')
            ->getResult();
    }
}
