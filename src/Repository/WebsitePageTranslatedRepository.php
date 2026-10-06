<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\WebsitePageTranslated;
use App\Repository\Contract\AbstractTranslatedRepository;
use NeoPHP\Package\Orm\Contract\AbstractRepository;

class WebsitePageTranslatedRepository extends AbstractTranslatedRepository
{
    protected string $entityClass = WebsitePageTranslated::class;
}
