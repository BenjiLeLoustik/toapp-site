<?php

declare(strict_types=1);

namespace App\Admin\Helper;

use App\Admin\Exception\AdminException;
use App\Entity\Technology;
use App\Helper\SlugHelper;
use NeoPHP\Package\Orm\Contract\EntityManagerInterface;

class TechnologyAdminHelper
{
    public function __construct(
        private EntityManagerInterface $entityManager,
    ) {
    }

    public function all(): array
    {
        return array_map(static fn (Technology $technology): array => [
            'id' => $technology->getId(),
            'name' => $technology->getName(),
            'slug' => $technology->getSlug(),
            'projects' => count($technology->getProjects()),
        ], $this->entityManager->getRepository(Technology::class)->findBy([], ['name' => 'ASC']));
    }

    public function slugs(): array
    {
        return array_column($this->all(), 'slug');
    }

    public function get(string $slug): Technology
    {
        $technology = $this->entityManager->getRepository(Technology::class)->findOneBy(['slug' => $slug]);

        if (!$technology instanceof Technology) {
            throw new AdminException(sprintf('The technology "%s" does not exist.', $slug));
        }

        return $technology;
    }

    public function add(string $name, ?string $slug = null): Technology
    {
        $name = trim($name);
        $slug = SlugHelper::slugify($slug ?? $name);

        if ($name === '' || $slug === '') {
            throw new AdminException('The name of the technology cannot be empty.');
        }

        $this->assertFreeSlug($slug);

        $technology = (new Technology())
            ->setName($name)
            ->setSlug($slug);

        $this->entityManager->persist($technology);
        $this->entityManager->flush();

        return $technology;
    }

    public function rename(string $slug, string $name, ?string $newSlug = null): Technology
    {
        $technology = $this->get($slug);
        $name = trim($name);

        if ($name === '') {
            throw new AdminException('The name of the technology cannot be empty.');
        }

        $technology->setName($name);

        if ($newSlug !== null) {
            $newSlug = SlugHelper::slugify($newSlug);

            if ($newSlug !== $slug) {
                $this->assertFreeSlug($newSlug);
                $technology->setSlug($newSlug);
            }
        }

        $this->entityManager->flush();

        return $technology;
    }

    public function delete(string $slug, bool $detach = false): int
    {
        $technology = $this->get($slug);
        $projects = iterator_to_array($technology->getProjects());

        if ($projects !== [] && !$detach) {
            throw new AdminException(sprintf('The technology "%s" is used by %d project(s): merge it or use --force to detach it.', $slug, count($projects)));
        }

        foreach ($projects as $project) {
            $project->removeTechnology($technology);
        }

        $this->entityManager->remove($technology);
        $this->entityManager->flush();

        return count($projects);
    }

    public function merge(string $sourceSlug, string $targetSlug): int
    {
        if ($sourceSlug === $targetSlug) {
            throw new AdminException('The source and the target technologies must be different.');
        }

        $source = $this->get($sourceSlug);
        $target = $this->get($targetSlug);
        $projects = iterator_to_array($source->getProjects());

        foreach ($projects as $project) {
            $project->removeTechnology($source);
            $project->addTechnology($target);
        }

        $this->entityManager->remove($source);
        $this->entityManager->flush();

        return count($projects);
    }

    private function assertFreeSlug(string $slug): void
    {
        if ($this->entityManager->getRepository(Technology::class)->findOneBy(['slug' => $slug]) !== null) {
            throw new AdminException(sprintf('A technology with the slug "%s" already exists.', $slug));
        }
    }
}