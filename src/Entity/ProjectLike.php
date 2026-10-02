<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\ProjectLikeRepository;
use NeoPHP\Package\Orm\Mapping as ORM;

#[ORM\Entity(repository: ProjectLikeRepository::class)]
#[ORM\Index(columns: ['user_id', 'project_id'], unique: true)]
class ProjectLike
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(target: Project::class, inversedBy: 'likes', nullable: false)]
    private ?Project $project = null;

    #[ORM\ManyToOne(target: User::class, inversedBy: 'projectLikes', nullable: false)]
    private ?User $user = null;

    #[ORM\Column(type: 'datetime', nullable: false)]
    private \DateTime $createdAt;

    public function __construct()
    {
        $this->createdAt = new \DateTime();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getProject(): ?Project
    {
        return $this->project;
    }

    public function setProject(Project $project): self
    {
        $this->project = $project;
        return $this;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(User $user): self
    {
        $this->user = $user;
        return $this;
    }

    public function getCreatedAt(): \DateTime
    {
        return $this->createdAt;
    }
}
