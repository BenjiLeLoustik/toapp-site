<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\User;
use NeoPHP\Package\Orm\Contract\AbstractRepository;

class UserRepository extends AbstractRepository
{
    protected string $entityClass = User::class;
}
