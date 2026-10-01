<?php

declare(strict_types=1);

namespace App\Entity;

use App\Entity\Contract\TranslatedTrait;
use App\Repository\CategoryTranslatedRepository;
use NeoPHP\Package\Orm\Mapping as ORM;

#[ORM\Entity(repository: CategoryTranslatedRepository::class)]
#[ORM\Index(columns: ['category', 'locale'], unique: true)]
class CategoryTranslated
{
    use TranslatedTrait;

    #[ORM\ManyToOne(target: Category::class, inversedBy: 'translations', nullable: false)]
    private ?Category $category = null;

    #[ORM\Column(type: 'string', length: 100, nullable: false)]
    private string $name;

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

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;
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
