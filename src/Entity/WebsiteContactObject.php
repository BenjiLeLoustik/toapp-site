<?php

declare(strict_types=1);

namespace App\Entity;

use App\Entity\Contract\AbstractEntity;
use App\Entity\Contract\TranslatableInterface;
use App\Entity\Contract\TranslatableTrait;
use App\Repository\WebsiteContactObjectRepository;
use NeoPHP\Package\Orm\Collection\ArrayCollection;
use NeoPHP\Package\Orm\Contract\CollectionInterface;
use NeoPHP\Package\Orm\Mapping as ORM;

#[ORM\Entity(repository: WebsiteContactObjectRepository::class)]
class WebsiteContactObject extends AbstractEntity implements TranslatableInterface
{
    use TranslatableTrait;

    #[ORM\Column(type: 'string', length: 100, nullable: false, unique: true)]
    private string $slug;

    #[ORM\OneToMany(target: WebsiteContactObjectTranslated::class, mappedBy: 'object', cascade: ['persist', 'remove'], orphanRemoval: true)]
    private CollectionInterface $translations;

    #[ORM\OneToMany(target: WebsiteContactMessage::class, mappedBy: 'object')]
    private CollectionInterface $messages;

    public function __construct()
    {
        parent::__construct();

        $this->translations = new ArrayCollection();
        $this->messages = new ArrayCollection();
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

    public function getTranslations(): CollectionInterface
    {
        return $this->translations;
    }

    public function addTranslation(WebsiteContactObjectTranslated $translation): self
    {
        if (!$this->translations->contains($translation)) {
            $this->translations->add($translation);
            $translation->setObject($this);
        }

        return $this;
    }

    public function removeTranslation(WebsiteContactObjectTranslated $translation): self
    {
        $this->translations->removeElement($translation);
        return $this;
    }

    public function getMessages(): CollectionInterface
    {
        return $this->messages;
    }
}