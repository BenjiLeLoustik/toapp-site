<?php

declare(strict_types=1);

namespace App\Entity;

use App\Entity\Contract\AbstractEntity;
use App\Repository\UserNotificationSettingRepository;
use App\User\Enum\UserDigestFrequencyEnum;
use NeoPHP\Package\Orm\Mapping as ORM;

#[ORM\Entity(repository: UserNotificationSettingRepository::class)]
class UserNotificationSetting extends AbstractEntity
{
    #[ORM\OneToOne(target: User::class, nullable: false, onDelete: 'CASCADE')]
    private ?User $user = null;

    #[ORM\Column(type: 'boolean', nullable: false, default: 1)]
    private bool $emailProjectLikes = true;

    #[ORM\Column(type: 'boolean', nullable: false, default: 1)]
    private bool $emailProjectFavorites = true;

    #[ORM\Column(type: 'boolean', nullable: false, default: 0)]
    private bool $emailNewsletter = false;

    #[ORM\Column(enumType: UserDigestFrequencyEnum::class)]
    private UserDigestFrequencyEnum $digestFrequency = UserDigestFrequencyEnum::WEEKLY;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTime $activityNotifiedAt = null;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTime $digestSentAt = null;

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(User $user): self
    {
        $this->user = $user;
        return $this;
    }

    public function isEmailProjectLikes(): bool
    {
        return $this->emailProjectLikes;
    }

    public function setEmailProjectLikes(bool $emailProjectLikes): self
    {
        $this->emailProjectLikes = $emailProjectLikes;
        return $this;
    }

    public function isEmailProjectFavorites(): bool
    {
        return $this->emailProjectFavorites;
    }

    public function setEmailProjectFavorites(bool $emailProjectFavorites): self
    {
        $this->emailProjectFavorites = $emailProjectFavorites;
        return $this;
    }

    public function isEmailNewsletter(): bool
    {
        return $this->emailNewsletter;
    }

    public function setEmailNewsletter(bool $emailNewsletter): self
    {
        $this->emailNewsletter = $emailNewsletter;
        return $this;
    }

    public function getDigestFrequency(): UserDigestFrequencyEnum
    {
        return $this->digestFrequency;
    }

    public function setDigestFrequency(UserDigestFrequencyEnum $digestFrequency): self
    {
        $this->digestFrequency = $digestFrequency;
        return $this;
    }

    public function getActivityNotifiedAt(): ?\DateTime
    {
        return $this->activityNotifiedAt;
    }

    public function setActivityNotifiedAt(?\DateTime $activityNotifiedAt): self
    {
        $this->activityNotifiedAt = $activityNotifiedAt;
        return $this;
    }

    public function getDigestSentAt(): ?\DateTime
    {
        return $this->digestSentAt;
    }

    public function setDigestSentAt(?\DateTime $digestSentAt): self
    {
        $this->digestSentAt = $digestSentAt;
        return $this;
    }
}