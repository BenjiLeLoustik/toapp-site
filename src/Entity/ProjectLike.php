<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\ProjectLikeRepository;
use NeoPHP\Package\Orm\Mapping as ORM;

#[ORM\Entity(repository: ProjectLikeRepository::class)]
class ProjectLike
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(target: Project::class, inversedBy: 'likes', nullable: false)]
    private ?Project $project = null;

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

    public function getCreatedAt(): \DateTime
    {
        return $this->createdAt;
    }
}
