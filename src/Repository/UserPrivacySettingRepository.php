<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\UserPrivacySetting;
use NeoPHP\Package\Orm\Contract\AbstractRepository;

class UserPrivacySettingRepository extends AbstractRepository
{
    protected string $entityClass = UserPrivacySetting::class;
}
