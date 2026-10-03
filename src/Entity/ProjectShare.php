<?php

declare(strict_types=1);

namespace App\Entity;

use App\Entity\Contract\AbstractEntity;
use App\Project\Enum\ProjectShareEnum;
use App\Repository\ProjectShareRepository;
use NeoPHP\Package\Orm\Mapping as ORM;

#[ORM\Entity(repository: ProjectShareRepository::class)]
class ProjectShare extends AbstractEntity
{
    #[ORM\ManyToOne(target: Project::class, inversedBy: 'shares', nullable: false)]
    private Project $project;

    #[ORM\Column(enumType: ProjectShareEnum::class)]
    private ProjectShareEnum $type;

    #[ORM\ManyToOne(target: User::class, nullable: true)]
    private ?User $user = null;

    #[ORM\Column(type: 'string', length: 45, nullable: true)]
    private ?string $ip = null;

    public function getProject(): Project
    {
        return $this->project;
    }

    public function setProject(Project $project): self
    {
        $this->project = $project;
        return $this;
    }

    public function getType(): ProjectShareEnum
    {
        return $this->type;
    }

    public function setType(ProjectShareEnum $type): self
    {
        $this->type = $type;
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

    public function getIp(): ?string
    {
        return $this->ip;
    }

    public function setIp(?string $ip): self
    {
        $this->ip = $ip;
        return $this;
    }
}