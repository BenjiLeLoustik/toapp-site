<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\WebsiteContactMessage;
use NeoPHP\Package\Orm\Contract\AbstractRepository;

class WebsiteContactMessageRepository extends AbstractRepository
{
    protected string $entityClass = WebsiteContactMessage::class;
}
