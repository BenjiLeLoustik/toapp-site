<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\CategoryRepository;
use NeoPHP\Package\Orm\Collection\ArrayCollection;
use NeoPHP\Package\Orm\Contract\CollectionInterface;
use NeoPHP\Package\Orm\Mapping as ORM;

#[ORM\Entity(repository: CategoryRepository::class)]
class Category
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: 'string', length: 150, nullable: false, unique: true)]
    private string $slug;

    #[ORM\Column(type: 'string', length: 50, nullable: false)]
    private string $icon;

    #[ORM\Column(type: 'datetime')]
    private \DateTime $createdAt;

    #[ORM\OneToMany(target: CategoryTranslated::class, mappedBy: 'category', cascade: ['persist', 'remove'], orphanRemoval: true)]
    private CollectionInterface $translations;

    public function __construct()
    {
        $this->createdAt = new \DateTime();
        $this->translations = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getSlug(): string
    {
        return $this->slug;
    }

    public function setSlug(string $slug): self
    {
        $this->slug = $slug;
        return $this;
    }

    public function getIcon(): string
    {
        return $this->icon;
    }

    public function setIcon(string $icon): self
    {
        $this->icon = $icon;
        return $this;
    }

    public function getCreatedAt(): \DateTime
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTime $createdAt): self
    {
        $this->createdAt = $createdAt;
        return $this;
    }

    public function getTranslations(): CollectionInterface
    {
        return $this->translations;
    }
}
