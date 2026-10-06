<?php

declare(strict_types=1);

namespace App\Entity;

use App\Entity\Contract\AbstractEntity;
use App\Repository\WebsiteContactMessageRepository;
use App\Website\Enum\WebsiteContactMessageStatusEnum;
use NeoPHP\Package\Orm\Mapping as ORM;

#[ORM\Entity(repository: WebsiteContactMessageRepository::class)]
class WebsiteContactMessage extends AbstractEntity
{
    #[ORM\Column(type: 'string', length: 50, nullable: false)]
    private string $lastname;

    #[ORM\Column(type: 'string', length: 50, nullable: false)]
    private string $firstname;

    #[ORM\Column(type: 'string', length: 180, nullable: false)]
    private string $email;

    #[ORM\ManyToOne(target: WebsiteContactObject::class, inversedBy: 'messages', nullable: false)]
    private ?WebsiteContactObject $object = null;

    #[ORM\Column(type: 'text', nullable: false)]
    private string $message;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $website = null;

    #[ORM\Column(enumType: WebsiteContactMessageStatusEnum::class)]
    private WebsiteContactMessageStatusEnum $status = WebsiteContactMessageStatusEnum::NEW;

    #[ORM\Column(type: 'boolean', nullable: false, default: 0)]
    private bool $consent = false;

    public function getLastname(): string
    {
        return $this->lastname;
    }

    public function setLastname(string $lastname): self
    {
        $this->lastname = $lastname;
        return $this;
    }

    public function getFirstname(): string
    {
        return $this->firstname;
    }

    public function setFirstname(string $firstname): self
    {
        $this->firstname = $firstname;
        return $this;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function setEmail(string $email): self
    {
        $this->email = $email;
        return $this;
    }

    public function getObject(): ?WebsiteContactObject
    {
        return $this->object;
    }

    public function setObject(WebsiteContactObject $object): self
    {
        $this->object = $object;
        return $this;
    }

    public function getMessage(): string
    {
        return $this->message;
    }

    public function setMessage(string $message): self
    {
        $this->message = $message;
        return $this;
    }

    public function getWebsite(): ?string
    {
        return $this->website;
    }

    public function setWebsite(?string $website): self
    {
        $this->website = $website;
        return $this;
    }

    public function getStatus(): WebsiteContactMessageStatusEnum
    {
        return $this->status;
    }

    public function setStatus(WebsiteContactMessageStatusEnum $status): self
    {
        $this->status = $status;
        return $this;
    }

    public function hasConsent(): bool
    {
        return $this->consent;
    }

    public function setConsent(bool $consent): self
    {
        $this->consent = $consent;
        return $this;
    }
}