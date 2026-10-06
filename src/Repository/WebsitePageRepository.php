<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\WebsitePage;
use NeoPHP\Package\Orm\Contract\AbstractRepository;

class WebsitePageRepository extends AbstractRepository
{
    protected string $entityClass = WebsitePage::class;

    public function findPublishedBySlug(string $slug): ?WebsitePage
    {
        return $this->findOneBy(['slug' => $slug, 'published' => true]);
    }
}