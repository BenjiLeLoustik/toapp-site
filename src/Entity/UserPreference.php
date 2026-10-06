<?php

declare(strict_types=1);

namespace App\Entity;

use App\Entity\Contract\AbstractEntity;
use App\Repository\UserPreferenceRepository;
use App\User\Enum\UserDateFormatEnum;
use App\User\Enum\UserNumberFormatEnum;
use NeoPHP\Package\Orm\Mapping as ORM;

#[ORM\Entity(repository: UserPreferenceRepository::class)]
class UserPreference extends AbstractEntity
{
    #[ORM\OneToOne(target: User::class, nullable: false, onDelete: 'CASCADE')]
    private ?User $user = null;

    #[ORM\Column(type: 'string', length: 20, nullable: true)]
    private ?string $theme = null;

    #[ORM\Column(type: 'string', length: 20, nullable: true)]
    private ?string $accent = null;

    #[ORM\Column(type: 'string', length: 20, nullable: true)]
    private ?string $scale = null;

    #[ORM\Column(type: 'string', length: 10, nullable: true)]
    private ?string $locale = null;

    #[ORM\Column(enumType: UserDateFormatEnum::class)]
    private UserDateFormatEnum $dateFormat = UserDateFormatEnum::DMY;

    #[ORM\Column(type: 'string', length: 64, nullable: false)]
    private string $timezone = 'Europe/Paris';

    #[ORM\Column(enumType: UserNumberFormatEnum::class)]
    private UserNumberFormatEnum $numberFormat = UserNumberFormatEnum::SPACE_COMMA;

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(User $user): self
    {
        $this->user = $user;
        return $this;
    }

    public function getTheme(): ?string
    {
        return $this->theme;
    }

    public function setTheme(?string $theme): self
    {
        $this->theme = $theme;
        return $this;
    }

    public function getAccent(): ?string
    {
        return $this->accent;
    }

    public function setAccent(?string $accent): self
    {
        $this->accent = $accent;
        return $this;
    }

    public function getScale(): ?string
    {
        return $this->scale;
    }

    public function setScale(?string $scale): self
    {
        $this->scale = $scale;
        return $this;
    }

    public function getLocale(): ?string
    {
        return $this->locale;
    }

    public function setLocale(?string $locale): self
    {
        $this->locale = $locale;
        return $this;
    }

    public function getDateFormat(): UserDateFormatEnum
    {
        return $this->dateFormat;
    }

    public function setDateFormat(UserDateFormatEnum $dateFormat): self
    {
        $this->dateFormat = $dateFormat;
        return $this;
    }

    public function getTimezone(): string
    {
        return $this->timezone;
    }

    public function setTimezone(string $timezone): self
    {
        $this->timezone = $timezone;
        return $this;
    }

    public function getNumberFormat(): UserNumberFormatEnum
    {
        return $this->numberFormat;
    }

    public function setNumberFormat(UserNumberFormatEnum $numberFormat): self
    {
        $this->numberFormat = $numberFormat;
        return $this;
    }
}