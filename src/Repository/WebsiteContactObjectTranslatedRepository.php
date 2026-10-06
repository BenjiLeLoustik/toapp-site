<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\WebsiteContactObjectTranslated;
use App\Repository\Contract\AbstractTranslatedRepository;

class WebsiteContactObjectTranslatedRepository extends AbstractTranslatedRepository
{
    protected string $entityClass = WebsiteContactObjectTranslated::class;
}
