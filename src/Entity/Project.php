<?php

declare(strict_types=1);

namespace App\Entity;

use App\Entity\Contract\AbstractEntity;
use App\Project\Enum\ProjectVisibilityEnum;
use App\Repository\ProjectRepository;
use NeoPHP\Package\Orm\Collection\ArrayCollection;
use NeoPHP\Package\Orm\Contract\CollectionInterface;
use NeoPHP\Package\Orm\Mapping as ORM;

#[ORM\Entity(repository: ProjectRepository::class)]
class Project extends AbstractEntity
{
    #[ORM\Column(type: 'string', length: 150, nullable: false)]
    private string $name;

    #[ORM\Column(type: 'string', length: 200, nullable: false, unique: true)]
    private string $slug;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $cover = null;

    #[ORM\Column(type: 'string', length: 512, nullable: true)]
    private ?string $shortDescription = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $description = null;

    #[ORM\ManyToOne(target: Category::class, inversedBy: 'projects', nullable: true)]
    private ?Category $category = null;

    #[ORM\ManyToOne(target: User::class, inversedBy: 'projects', nullable: false)]
    private ?User $user = null;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTime $publishedAt = null;

    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $features = null;

    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $externalLinks = null;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTime $updatedAt = null;

    #[ORM\Column(enumType: ProjectVisibilityEnum::class)]
    private ?ProjectVisibilityEnum $visibility;

    #[ORM\ManyToMany(target: Technology::class, inversedBy: 'projects')]
    private CollectionInterface $technologies;

    #[ORM\OneToMany(target: ProjectLike::class, mappedBy: 'project', cascade: ['persist', 'remove'], orphanRemoval: true)]
    private CollectionInterface $likes;

    public function __construct()
    {
        $this->technologies = new ArrayCollection();
        $this->likes = new ArrayCollection();
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

    public function getShortDescription(): ?string
    {
        return $this->shortDescription;
    }

    public function setShortDescription(?string $shortDescription): self
    {
        $this->shortDescription = $shortDescription;
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

    public function getPublishedAt(): ?\DateTime
    {
        return $this->publishedAt;
    }

    public function setPublishedAt(?\DateTime $publishedAt): self
    {
        $this->publishedAt = $publishedAt;
        return $this;
    }

    public function getFeatures(): ?array
    {
        return $this->features;
    }

    public function setFeatures(?array $features): self
    {
        $this->features = $features;
        return $this;
    }

    public function getExternalLinks(): ?array
    {
        return $this->externalLinks;
    }

    public function setExternalLinks(?array $externalLinks): self
    {
        $this->externalLinks = $externalLinks;
        return $this;
    }

    public function getVisibility(): string
    {
        return $this->visibility->label();
    }

    public function setVisibility(ProjectVisibilityEnum $visibility): self
    {
        $this->visibility = $visibility;
        return $this;
    }

    public function getUpdatedAt(): ?\DateTime
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(?\DateTime $updatedAt): self
    {
        $this->updatedAt = $updatedAt;
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

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): self
    {
        $this->user = $user;
        return $this;
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
