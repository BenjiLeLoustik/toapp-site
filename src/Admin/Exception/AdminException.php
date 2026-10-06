<?php

declare(strict_types=1);

namespace App\Admin\Exception;

use NeoPHP\Component\Exception\FrameworkException;

class AdminException extends FrameworkException
{
    protected int $statusCode = 400;
}