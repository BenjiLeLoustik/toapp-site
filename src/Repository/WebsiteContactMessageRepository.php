<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\WebsiteContactMessage;
use App\Website\Enum\WebsiteContactMessageStatusEnum;
use NeoPHP\Package\Orm\Contract\AbstractRepository;

class WebsiteContactMessageRepository extends AbstractRepository
{
    protected string $entityClass = WebsiteContactMessage::class;

    public function findLatest(?WebsiteContactMessageStatusEnum $status, int $limit): array
    {
        $queryBuilder = $this->createQueryBuilder('m')
            ->orderBy('m.createdAt', 'DESC')
            ->addOrderBy('m.id', 'DESC')
            ->setMaxResults($limit);

        if ($status !== null) {
            $queryBuilder
                ->where('m.status = :status')
                ->setParameter('status', $status->value);
        }

        return $queryBuilder->getResult();
    }
}