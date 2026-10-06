<?php

declare(strict_types=1);

namespace App\Entity;

use App\Entity\Contract\AbstractTranslatedEntity;
use App\Repository\WebsiteContactTypeTranslatedRepository;
use NeoPHP\Package\Orm\Mapping as ORM;

#[ORM\Entity(repository: WebsiteContactTypeTranslatedRepository::class)]
#[ORM\Index(columns: ['type_id', 'locale'], unique: true)]
class WebsiteContactTypeTranslated extends AbstractTranslatedEntity
{
    #[ORM\ManyToOne(target: WebsiteContactType::class, inversedBy: 'translations', nullable: false, onDelete: 'CASCADE')]
    private ?WebsiteContactType $type = null;

    public function getType(): ?WebsiteContactType
    {
        return $this->type;
    }

    public function setType(?WebsiteContactType $type): self
    {
        $this->type = $type;
        return $this;
    }
}