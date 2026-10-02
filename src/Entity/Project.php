<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\ProjectRepository;
use NeoPHP\Package\Orm\Collection\ArrayCollection;
use NeoPHP\Package\Orm\Contract\CollectionInterface;
use NeoPHP\Package\Orm\Mapping as ORM;

#[ORM\Entity(repository: ProjectRepository::class)]
class Project
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: 'string', length: 150, nullable: false)]
    private string $name;

    #[ORM\Column(type: 'string', length: 200, nullable: false, unique: true)]
    private string $slug;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $cover = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $description = null;

    #[ORM\ManyToOne(target: Category::class, inversedBy: 'projects', nullable: true)]
    private ?Category $category = null;

    #[ORM\Column(type: 'datetime', nullable: false)]
    private \DateTime $createdAt;

    #[ORM\ManyToMany(target: Technology::class, inversedBy: 'projects')]
    private CollectionInterface $technologies;

    #[ORM\OneToMany(target: ProjectLike::class, mappedBy: 'project', cascade: ['persist', 'remove'], orphanRemoval: true)]
    private CollectionInterface $likes;

    public function __construct()
    {
        $this->createdAt = new \DateTime();
        $this->technologies = new ArrayCollection();
        $this->likes = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;
        return $this;
    }

    public function getSlug(): ?string
    {
        return $this->slug;
    }

    public function setSlug(string $slug): self
    {
        $this->slug = $slug;
        return $this;
    }

    public function getCover(): ?string
    {
        return $this->cover;
    }

    public function setCover(?string $cover): self
    {
        $this->cover = $cover;
        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(string $description): self
    {
        $this->description = $description;
        return $this;
    }

    public function getCategory(): ?Category
    {
        return $this->category;
    }

    public function setCategory(?Category $category): self
    {
        $this->category = $category;
        return $this;
    }

    public function getCreatedAt(): \DateTime
    {
        return $this->createdAt;
    }

    public function getTechnologies(): CollectionInterface
    {
        return $this->technologies;
    }

    public function addTechnology(Technology $technology): self
    {
        if (!$this->technologies->contains($technology)) {
            $this->technologies->add($technology);
        }

        return $this;
    }

    public function removeTechnology(Technology $technology): self
    {
        $this->technologies->removeElement($technology);
        return $this;
    }

    public function getLikes(): CollectionInterface
    {
        return $this->likes;
    }

    public function addLike(ProjectLike $like): self
    {
        if (!$this->likes->contains($like)) {
            $this->likes->add($like);
            $like->setProject($this);
        }

        return $this;
    }

    public function removeLike(ProjectLike $like): self
    {
        $this->likes->removeElement($like);

        return $this;
    }
}
