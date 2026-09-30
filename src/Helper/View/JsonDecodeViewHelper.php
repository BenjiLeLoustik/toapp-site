<?php

declare(strict_types=1);

namespace App\Helper\View;

use NeoPHP\Component\View\Contract\ViewFilterInterface;

class JsonDecodeViewHelper implements ViewFilterInterface
{

    public function getName(): string
    {
        return 'json_decode';
    }

    public function __invoke(string $json, bool $associative = true): mixed
    {
        return json_decode($json, $associative, 512, JSON_THROW_ON_ERROR);
    }
}