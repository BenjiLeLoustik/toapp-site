<?php

declare(strict_types=1);

namespace App\Entity;

use App\Entity\Contract\AbstractEntity;
use App\Entity\Contract\TranslatableInterface;
use App\Entity\Contract\TranslatableTrait;
use App\Repository\WebsitePageRepository;
use NeoPHP\Package\Orm\Collection\ArrayCollection;
use NeoPHP\Package\Orm\Contract\CollectionInterface;
use NeoPHP\Package\Orm\Mapping as ORM;

#[ORM\Entity(repository: WebsitePageRepository::class)]
class WebsitePage extends AbstractEntity implements TranslatableInterface
{
    use TranslatableTrait;

    #[ORM\Column(type: 'string', length: 150, nullable: false, unique: true)]
    private string $slug;

    #[ORM\Column(type: 'boolean', nullable: false, default: 1)]
    private bool $published = true;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTime $updatedAt = null;

    #[ORM\OneToMany(target: WebsitePageTranslated::class, mappedBy: 'page', cascade: ['persist', 'remove'], orphanRemoval: true)]
    private CollectionInterface $translations;

    public function __construct()
    {
        parent::__construct();

        $this->translations = new ArrayCollection();
    }

    public function getSlug(): string
    {
        return $this->slug;
    }

    public function setSlug(string $slug): self
    {
        $this->slug = $slug;
        return $this;
    }

    public function isPublished(): bool
    {
        return $this->published;
    }

    public function setPublished(bool $published): self
    {
        $this->published = $published;
        return $this;
    }

    public function getUpdatedAt(): ?\DateTime
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(?\DateTime $updatedAt): self
    {
        $this->updatedAt = $updatedAt;
        return $this;
    }

    public function getTranslations(): CollectionInterface
    {
        return $this->translations;
    }

    public function addTranslation(WebsitePageTranslated $translation): self
    {
        if (!$this->translations->contains($translation)) {
            $this->translations->add($translation);
            $translation->setPage($this);
        }

        return $this;
    }

    public function removeTranslation(WebsitePageTranslated $translation): self
    {
        $this->translations->removeElement($translation);
        return $this;
    }
}