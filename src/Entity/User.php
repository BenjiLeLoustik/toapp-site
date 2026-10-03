<?php

declare(strict_types=1);

namespace App\Entity;

use App\Entity\Contract\AbstractEntity;
use App\Repository\UserRepository;
use NeoPHP\Package\Orm\Collection\ArrayCollection;
use NeoPHP\Package\Orm\Contract\CollectionInterface;
use NeoPHP\Package\Orm\Mapping as ORM;
use NeoPHP\Package\Security\Contract\PasswordAuthenticatedUserInterface;
use NeoPHP\Package\Security\Contract\UserInterface;

#[ORM\Entity(repository: UserRepository::class)]
class User extends AbstractEntity implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Column(length: 180, unique: true)]
    private ?string $email = null;

    #[ORM\Column(type: 'string', length: 50, nullable: false, unique: false)]
    private string $firstname;

    #[ORM\Column(type: 'string', length: 50, nullable: false, unique: false)]
    private string $lastname;

    #[ORM\Column(type: 'string', length: 150, nullable: false, unique: true)]
    private string $slug;

    #[ORM\Column(type: 'string', length: 255, nullable: false, unique: false)]
    private ?string $avatar = null;

    #[ORM\Column(type: 'string', length: 255, nullable: false, unique: true)]
    private string $username;

    #[ORM\Column(type: 'boolean', nullable: false, default: 0)]
    private bool $certified = false;

    #[ORM\Column(type: 'json')]
    private array $roles = [];

    #[ORM\Column]
    private ?string $password = null;

    #[ORM\OneToMany(target: Project::class, mappedBy: 'user')]
    private CollectionInterface $projects;

    #[ORM\OneToMany(ProjectLike::class, mappedBy: 'user')]
    private CollectionInterface $projectLikes;

    public function __construct()
    {
        $this->projects = new ArrayCollection();
        $this->projectLikes = new ArrayCollection();
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): static
    {
        $this->email = $email;

        return $this;
    }

    public function getUserIdentifier(): string
    {
        return (string) $this->email;
    }

    public function getRoles(): array
    {
        return array_values(array_unique([...$this->roles, 'ROLE_USER']));
    }

    public function setRoles(array $roles): static
    {
        $this->roles = $roles;

        return $this;
    }

    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(string $password): static
    {
        $this->password = $password;

        return $this;
    }

    public function getFirstname(): string
    {
        return $this->firstname;
    }

    public function setFirstname(string $firstname): static
    {
        $this->firstname = $firstname;
        return $this;
    }

    public function getLastname(): string
    {
        return $this->lastname;
    }

    public function setLastname(string $lastname): static
    {
        $this->lastname = $lastname;
        return $this;
    }

    public function getSlug(): string
    {
        return $this->slug;
    }

    public function setSlug(string $slug): static
    {
        $this->slug = $slug;
        return $this;
    }

    public function getAvatar(): ?string
    {
        return $this->avatar;
    }

    public function setAvatar(?string $avatar): static
    {
        $this->avatar = $avatar;
        return $this;
    }

    public function getUsername(): string
    {
        return $this->username;
    }

    public function setUsername(string $username): static
    {
        $this->username = $username;
        return $this;
    }

    public function isCertified(): bool
    {
        return $this->certified;
    }

    public function setCertified(bool $certified): static
    {
        $this->certified = $certified;
        return $this;
    }

    public function getProjects(): CollectionInterface
    {
        return $this->projects;
    }

    public function addProject(Project $project): self
    {
        if (!$this->projects->contains($project)) {
            $this->projects->add($project);
            $project->setUser($this);
        }

        return $this;
    }

    public function removeProject(Project $project): self
    {
        $this->projects->removeElement($project);

        return $this;
    }

    public function getProjectLikes(): CollectionInterface
    {
        return $this->projectLikes;
    }

    public function addProjectLike(ProjectLike $projectLike): self
    {
        if (!$this->projectLikes->contains($projectLike)) {
            $this->projectLikes->add($projectLike);
            $projectLike->setUser($this);
        }

        return $this;
    }

    public function removeProjectLike(ProjectLike $projectLike): self
    {
        $this->projectLikes->removeElement($projectLike);

        return $this;
    }
}
