<?php

declare(strict_types=1);

namespace App\Admin\Helper;

use App\Admin\Exception\AdminException;
use App\Entity\Technology;
use App\Helper\SlugHelper;
use NeoPHP\Package\Orm\Contract\EntityManagerInterface;

class TechnologyAdminHelper
{
    use AdminValidationTrait;

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
            throw new AdminException('The technology "{slug}" does not exist.', 0, null, ['slug' => $slug]);
        }

        return $technology;
    }

    public function add(string $name, ?string $slug = null): Technology
    {
        $name = $this->notEmpty($name, 'name of the technology');
        $slug = SlugHelper::slugify($slug ?? $name);

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
        $technology->setName($this->notEmpty($name, 'name of the technology'));

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
            throw new AdminException(
                'The technology "{slug}" is used by {count} project(s): merge it or use --force to detach it.',
                0,
                null,
                ['slug' => $slug, 'count' => count($projects)]
            );
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
        if ($slug === '') {
            throw new AdminException('The slug of the technology cannot be empty.');
        }

        if ($this->entityManager->getRepository(Technology::class)->findOneBy(['slug' => $slug]) !== null) {
            throw new AdminException('A technology with the slug "{slug}" already exists.', 0, null, ['slug' => $slug]);
        }
    }
}