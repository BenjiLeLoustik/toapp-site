<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\UserPreference;
use NeoPHP\Package\Orm\Contract\AbstractRepository;

class UserPreferenceRepository extends AbstractRepository
{
    protected string $entityClass = UserPreference::class;
}
