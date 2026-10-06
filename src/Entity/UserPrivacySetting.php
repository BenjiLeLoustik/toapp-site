<?php

declare(strict_types=1);

namespace App\Entity;

use App\Entity\Contract\AbstractEntity;
use App\Repository\UserPrivacySettingRepository;
use NeoPHP\Package\Orm\Mapping as ORM;

#[ORM\Entity(repository: UserPrivacySettingRepository::class)]
class UserPrivacySetting extends AbstractEntity
{
    #[ORM\OneToOne(target: User::class, nullable: false, onDelete: 'CASCADE')]
    private ?User $user = null;

    #[ORM\Column(type: 'boolean', nullable: false, default: 1)]
    private bool $profilePublic = true;

    #[ORM\Column(type: 'boolean', nullable: false, default: 0)]
    private bool $showEmail = false;

    #[ORM\Column(type: 'boolean', nullable: false, default: 1)]
    private bool $showLocation = true;

    #[ORM\Column(type: 'boolean', nullable: false, default: 1)]
    private bool $showStats = true;

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(User $user): self
    {
        $this->user = $user;
        return $this;
    }

    public function isProfilePublic(): bool
    {
        return $this->profilePublic;
    }

    public function setProfilePublic(bool $profilePublic): self
    {
        $this->profilePublic = $profilePublic;
        return $this;
    }

    public function isShowEmail(): bool
    {
        return $this->showEmail;
    }

    public function setShowEmail(bool $showEmail): self
    {
        $this->showEmail = $showEmail;
        return $this;
    }

    public function isShowLocation(): bool
    {
        return $this->showLocation;
    }

    public function setShowLocation(bool $showLocation): self
    {
        $this->showLocation = $showLocation;
        return $this;
    }

    public function isShowStats(): bool
    {
        return $this->showStats;
    }

    public function setShowStats(bool $showStats): self
    {
        $this->showStats = $showStats;
        return $this;
    }
}