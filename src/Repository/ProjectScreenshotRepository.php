<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\ProjectScreenshot;
use NeoPHP\Package\Orm\Contract\AbstractRepository;

class ProjectScreenshotRepository extends AbstractRepository
{
    protected string $entityClass = ProjectScreenshot::class;
}
