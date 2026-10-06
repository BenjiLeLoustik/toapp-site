<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\WebsiteContactTypeTranslated;
use App\Repository\Contract\AbstractTranslatedRepository;

class WebsiteContactTypeTranslatedRepository extends AbstractTranslatedRepository
{
    protected string $entityClass = WebsiteContactTypeTranslated::class;
}
