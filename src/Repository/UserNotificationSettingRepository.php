<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\UserNotificationSetting;
use NeoPHP\Package\Orm\Contract\AbstractRepository;

class UserNotificationSettingRepository extends AbstractRepository
{
    protected string $entityClass = UserNotificationSetting::class;
}
