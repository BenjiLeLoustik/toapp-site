<?php

declare(strict_types=1);

namespace App\Entity;

use App\Entity\Contract\AbstractEntity;
use App\Repository\WebsiteContactConfigRepository;
use NeoPHP\Package\Orm\Mapping as ORM;

#[ORM\Entity(repository: WebsiteContactConfigRepository::class)]
class WebsiteContactConfig extends AbstractEntity
{
    #[ORM\ManyToOne(target: WebsiteContactType::class, inversedBy: 'configs', nullable: false, onDelete: 'CASCADE')]
    private ?WebsiteContactType $type = null;

    #[ORM\Column(type: 'string', length: 255, nullable: false)]
    private string $value;

    #[ORM\Column(type: 'boolean', nullable: false, default: 1)]
    private bool $enabled = true;

    public function getType(): ?WebsiteContactType
    {
        return $this->type;
    }

    public function setType(?WebsiteContactType $type): self
    {
        $this->type = $type;
        return $this;
    }

    public function getValue(): string
    {
        return $this->value;
    }

    public function setValue(string $value): self
    {
        $this->value = $value;
        return $this;
    }

    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    public function setEnabled(bool $enabled): self
    {
        $this->enabled = $enabled;
        return $this;
    }
}