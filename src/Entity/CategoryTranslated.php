<?php

declare(strict_types=1);

namespace App\Entity;

use App\Entity\Contract\AbstractEntity;
use App\Entity\Contract\AbstractTranslatedEntity;
use App\Repository\CategoryTranslatedRepository;
use NeoPHP\Package\Orm\Mapping as ORM;

#[ORM\Entity(repository: CategoryTranslatedRepository::class)]
#[ORM\Index(columns: ['category', 'locale'], unique: true)]
class CategoryTranslated extends AbstractTranslatedEntity
{
    #[ORM\ManyToOne(target: Category::class, inversedBy: 'translations', nullable: false)]
    private ?Category $category = null;

    #[ORM\Column(type: 'text', nullable: false)]
    private string $description;

    public function getCategory(): ?Category
    {
        return $this->category;
    }

    public function setCategory(?Category $category): self
    {
        $this->category = $category;
        return $this;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function setDescription(string $description): self
    {
        $this->description = $description;
        return $this;
    }
}
