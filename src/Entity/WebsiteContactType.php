<?php

declare(strict_types=1);

namespace App\Entity;

use App\Entity\Contract\AbstractEntity;
use App\Entity\Contract\TranslatableInterface;
use App\Entity\Contract\TranslatableTrait;
use App\Repository\WebsiteContactTypeRepository;
use App\Website\Enum\WebsiteContactFormatEnum;
use NeoPHP\Package\Orm\Collection\ArrayCollection;
use NeoPHP\Package\Orm\Contract\CollectionInterface;
use NeoPHP\Package\Orm\Mapping as ORM;

#[ORM\Entity(repository: WebsiteContactTypeRepository::class)]
class WebsiteContactType extends AbstractEntity implements TranslatableInterface
{
    use TranslatableTrait;

    #[ORM\Column(type: 'string', length: 100, nullable: false, unique: true)]
    private string $slug;

    #[ORM\Column(type: 'string', length: 50, nullable: false)]
    private string $icon;

    #[ORM\Column(enumType: WebsiteContactFormatEnum::class)]
    private WebsiteContactFormatEnum $format = WebsiteContactFormatEnum::TEXT;

    #[ORM\OneToMany(target: WebsiteContactTypeTranslated::class, mappedBy: 'type', cascade: ['persist', 'remove'], orphanRemoval: true)]
    private CollectionInterface $translations;

    #[ORM\OneToMany(target: WebsiteContactConfig::class, mappedBy: 'type', cascade: ['persist', 'remove'], orphanRemoval: true)]
    private CollectionInterface $configs;

    public function __construct()
    {
        parent::__construct();

        $this->translations = new ArrayCollection();
        $this->configs = new ArrayCollection();
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

    public function getIcon(): string
    {
        return $this->icon;
    }

    public function setIcon(string $icon): self
    {
        $this->icon = $icon;
        return $this;
    }

    public function getFormat(): WebsiteContactFormatEnum
    {
        return $this->format;
    }

    public function setFormat(WebsiteContactFormatEnum $format): self
    {
        $this->format = $format;
        return $this;
    }

    public function getTranslations(): CollectionInterface
    {
        return $this->translations;
    }

    public function addTranslation(WebsiteContactTypeTranslated $translation): self
    {
        if (!$this->translations->contains($translation)) {
            $this->translations->add($translation);
            $translation->setType($this);
        }

        return $this;
    }

    public function removeTranslation(WebsiteContactTypeTranslated $translation): self
    {
        $this->translations->removeElement($translation);
        return $this;
    }

    public function getConfigs(): CollectionInterface
    {
        return $this->configs;
    }

    public function addConfig(WebsiteContactConfig $config): self
    {
        if (!$this->configs->contains($config)) {
            $this->configs->add($config);
            $config->setType($this);
        }

        return $this;
    }

    public function removeConfig(WebsiteContactConfig $config): self
    {
        $this->configs->removeElement($config);
        return $this;
    }
}