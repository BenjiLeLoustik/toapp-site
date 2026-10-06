<?php

declare(strict_types=1);

namespace App\User\Event;

use App\Entity\User;
use NeoPHP\Component\Event\Contract\AbstractEvent;

class UserRegisteredEvent extends AbstractEvent
{
    public function __construct(
        public User $user,
    ) {
    }
}