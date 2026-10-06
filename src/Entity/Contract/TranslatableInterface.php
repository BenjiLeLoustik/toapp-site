<?php

namespace App\Entity\Contract;

use NeoPHP\Package\Orm\Contract\CollectionInterface;

interface TranslatableInterface
{
    public function getTranslations(): CollectionInterface;
}