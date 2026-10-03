<?php

declare(strict_types=1);

namespace App\Entity;

use App\Entity\Contract\AbstractEntity;
use App\Repository\ProjectLikeRepository;
use NeoPHP\Package\Orm\Mapping as ORM;

#[ORM\Entity(repository: ProjectLikeRepository::class)]
#[ORM\Index(columns: ['user_id', 'project_id'], unique: true)]
class ProjectLike extends AbstractEntity
{
    #[ORM\ManyToOne(target: Project::class, inversedBy: 'likes', nullable: false)]
    private ?Project $project = null;

    #[ORM\ManyToOne(target: User::class, inversedBy: 'projectLikes', nullable: false)]
    private ?User $user = null;

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
}
