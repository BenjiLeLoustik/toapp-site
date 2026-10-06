<?php

declare(strict_types=1);

namespace App\Entity;

use App\Entity\Contract\AbstractEntity;
use App\Project\Enum\ProjectViewSourceEnum;
use App\Repository\ProjectViewRepository;
use NeoPHP\Package\Orm\Mapping as ORM;

#[ORM\Entity(repository: ProjectViewRepository::class)]
class ProjectView extends AbstractEntity
{
    #[ORM\ManyToOne(target: Project::class, inversedBy: 'views', nullable: false)]
    private ?Project $project = null;

    #[ORM\ManyToOne(target: User::class, nullable: true)]
    private ?User $user = null;

    #[ORM\Column(type: 'string', length: 45, nullable: true)]
    private ?string $ip = null;

    #[ORM\Column(enumType: ProjectViewSourceEnum::class, nullable: true)]
    private ?ProjectViewSourceEnum $source = null;

    #[ORM\Column(type: 'string', length: 2, nullable: true)]
    private ?string $country = null;

    public function getProject(): ?Project
    {
        return $this->project;
    }

    public function setProject(?Project $project): self
    {
        $this->project = $project;
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

    public function getSource(): ProjectViewSourceEnum
    {
        return $this->source ?? ProjectViewSourceEnum::DIRECT;
    }

    public function setSource(?ProjectViewSourceEnum $source): self
    {
        $this->source = $source;
        return $this;
    }

    public function getCountry(): ?string
    {
        return $this->country;
    }

    public function setCountry(?string $country): self
    {
        $this->country = $country !== null ? strtoupper($country) : null;
        return $this;
    }
}