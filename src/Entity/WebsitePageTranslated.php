<?php

declare(strict_types=1);

namespace App\Entity;

use App\Entity\Contract\AbstractTranslatedEntity;
use App\Repository\WebsitePageTranslatedRepository;
use NeoPHP\Package\Orm\Mapping as ORM;

#[ORM\Entity(repository: WebsitePageTranslatedRepository::class)]
#[ORM\Index(columns: ['page_id', 'locale'], unique: true)]
class WebsitePageTranslated extends AbstractTranslatedEntity
{
    #[ORM\ManyToOne(target: WebsitePage::class, inversedBy: 'translations', nullable: false, onDelete: 'CASCADE')]
    private ?WebsitePage $page = null;

    #[ORM\Column(type: 'text', nullable: false)]
    private string $content;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $metaDescription = null;

    public function getPage(): ?WebsitePage
    {
        return $this->page;
    }

    public function setPage(?WebsitePage $page): self
    {
        $this->page = $page;
        return $this;
    }

    public function getContent(): string
    {
        return $this->content;
    }

    public function setContent(string $content): self
    {
        $this->content = $content;
        return $this;
    }

    public function getMetaDescription(): ?string
    {
        return $this->metaDescription;
    }

    public function setMetaDescription(?string $metaDescription): self
    {
        $this->metaDescription = $metaDescription;
        return $this;
    }
}