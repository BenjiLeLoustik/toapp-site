<?php

declare(strict_types=1);

namespace App\Entity;

use App\Entity\Contract\AbstractEntity;
use App\Repository\ProjectFavoriteRepository;
use NeoPHP\Package\Orm\Mapping as ORM;

#[ORM\Entity(repository: ProjectFavoriteRepository::class)]
#[ORM\Index(columns: ['user_id', 'project_id'], unique: true)]
class ProjectFavorite extends AbstractEntity
{
    #[ORM\ManyToOne(target: Project::class, inversedBy: 'favorites', nullable: false, onDelete: 'CASCADE')]
    private ?Project $project = null;

    #[ORM\ManyToOne(target: User::class, inversedBy: 'projectFavorites', nullable: false, onDelete: 'CASCADE')]
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
