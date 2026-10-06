<?php

declare(strict_types=1);

namespace App\Entity;

use App\Entity\Contract\AbstractTranslatedEntity;
use App\Repository\WebsiteContactObjectTranslatedRepository;
use NeoPHP\Package\Orm\Mapping as ORM;

#[ORM\Entity(repository: WebsiteContactObjectTranslatedRepository::class)]
#[ORM\Index(columns: ['object_id', 'locale'], unique: true)]
class WebsiteContactObjectTranslated extends AbstractTranslatedEntity
{
    #[ORM\ManyToOne(target: WebsiteContactObject::class, inversedBy: 'translations', nullable: false, onDelete: 'CASCADE')]
    private ?WebsiteContactObject $object = null;

    public function getObject(): ?WebsiteContactObject
    {
        return $this->object;
    }

    public function setObject(?WebsiteContactObject $object): self
    {
        $this->object = $object;
        return $this;
    }
}