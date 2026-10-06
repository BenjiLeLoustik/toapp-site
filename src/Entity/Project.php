<?php

declare(strict_types=1);

namespace App\Entity;

use App\Entity\Contract\AbstractEntity;
use App\Project\Enum\ProjectStatusEnum;
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

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $websiteUrl = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $repositoryUrl = null;

    #[ORM\ManyToOne(target: Category::class, inversedBy: 'projects', nullable: true)]
    private ?Category $category = null;

    #[ORM\ManyToOne(target: User::class, inversedBy: 'projects', nullable: false)]
    private ?User $user = null;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTime $publishedAt = null;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTime $archivedAt = null;

    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $features = null;

    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $externalLinks = null;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTime $updatedAt = null;

    #[ORM\Column(enumType: ProjectVisibilityEnum::class)]
    private ?ProjectVisibilityEnum $visibility;

    #[ORM\OneToMany(target: ProjectScreenshot::class, mappedBy: 'project', cascade: ['persist', 'remove'], orphanRemoval: true, orderBy: ['position' => 'ASC'])]
    private CollectionInterface $screenshots;

    #[ORM\ManyToMany(target: Technology::class, inversedBy: 'projects')]
    private CollectionInterface $technologies;

    #[ORM\OneToMany(target: ProjectLike::class, mappedBy: 'project', cascade: ['persist', 'remove'], orphanRemoval: true)]
    private CollectionInterface $likes;

    #[ORM\OneToMany(target: ProjectFavorite::class, mappedBy: 'project', cascade: ['persist', 'remove'], orphanRemoval: true)]
    private CollectionInterface $favorites;

    #[ORM\OneToMany(target: ProjectView::class, mappedBy: 'project', cascade: ['persist', 'remove'], orphanRemoval: true)]
    private CollectionInterface $views;

    #[ORM\OneToMany(target: ProjectShare::class, mappedBy: 'project', cascade: ['persist', 'remove'], orphanRemoval: true)]
    private CollectionInterface $shares;

    public function __construct()
    {
        parent::__construct();

        $this->visibility = ProjectVisibilityEnum::PUBLIC;
        $this->technologies = new ArrayCollection();
        $this->likes = new ArrayCollection();
        $this->favorites = new ArrayCollection();
        $this->screenshots = new ArrayCollection();
        $this->views = new ArrayCollection();
        $this->shares = new ArrayCollection();
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

    public function getWebsiteUrl(): ?string
    {
        return $this->websiteUrl;
    }

    public function setWebsiteUrl(?string $websiteUrl): self
    {
        $this->websiteUrl = $websiteUrl;
        return $this;
    }

    public function getRepositoryUrl(): ?string
    {
        return $this->repositoryUrl;
    }

    public function setRepositoryUrl(?string $repositoryUrl): self
    {
        $this->repositoryUrl = $repositoryUrl;
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

    public function getArchivedAt(): ?\DateTime
    {
        return $this->archivedAt;
    }

    public function setArchivedAt(?\DateTime $archivedAt): self
    {
        $this->archivedAt = $archivedAt;
        return $this;
    }

    public function isArchived(): bool
    {
        return $this->archivedAt !== null;
    }

    public function getStatus(): ProjectStatusEnum
    {
        return match (true) {
            $this->archivedAt !== null => ProjectStatusEnum::ARCHIVED,
            $this->publishedAt !== null => ProjectStatusEnum::PUBLISHED,
            default => ProjectStatusEnum::DRAFT,
        };
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

    public function getVisibilityEnum(): ProjectVisibilityEnum
    {
        return $this->visibility ?? ProjectVisibilityEnum::PUBLIC;
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

    public function getFavorites(): CollectionInterface
    {
        return $this->favorites;
    }

    public function addFavorite(ProjectFavorite $favorite): self
    {
        if (!$this->favorites->contains($favorite)) {
            $this->favorites->add($favorite);
            $favorite->setProject($this);
        }

        return $this;
    }

    public function removeFavorite(ProjectFavorite $favorite): self
    {
        $this->favorites->removeElement($favorite);
        return $this;
    }

    public function getScreenshots(): CollectionInterface
    {
        return $this->screenshots;
    }

    public function addScreenshot(ProjectScreenshot $screenshot): self
    {
        if (!$this->screenshots->contains($screenshot)) {
            $this->screenshots->add($screenshot);
            $screenshot->setProject($this);
        }

        return $this;
    }

    public function removeScreenshot(ProjectScreenshot $screenshot): self
    {
        if ($this->screenshots->removeElement($screenshot)) {
            if ($screenshot->getProject() === $this) {
                $screenshot->setProject(null);
            }
        }

        return $this;
    }

    public function getViews(): CollectionInterface
    {
        return $this->views;
    }

    public function addView(ProjectView $view): self
    {
        if (!$this->views->contains($view)) {
            $this->views->add($view);
            $view->setProject($this);
        }

        return $this;
    }

    public function removeView(ProjectView $view): self
    {
        $this->views->removeElement($view);
        return $this;
    }

    public function getShares(): CollectionInterface
    {
        return $this->shares;
    }

    public function addShare(ProjectShare $share): self
    {
        if (!$this->shares->contains($share)) {
            $this->shares->add($share);
            $share->setProject($this);
        }

        return $this;
    }

    public function removeShare(ProjectShare $share): self
    {
        $this->shares->removeElement($share);
        return $this;
    }
}