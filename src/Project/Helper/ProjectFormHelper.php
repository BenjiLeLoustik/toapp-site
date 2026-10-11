<?php

declare(strict_types=1);

namespace App\Project\Helper;

use App\Entity\Category;
use App\Entity\Project;
use App\Entity\ProjectScreenshot;
use App\Entity\Technology;
use App\Entity\User;
use App\Project\Enum\ProjectStatusEnum;
use App\Project\Enum\ProjectVisibilityEnum;
use App\User\Helper\UserPathHelper;
use NeoPHP\Component\Http\Request\UploadedFile;
use NeoPHP\Component\Upload\Exception\UploadException;
use NeoPHP\Component\Upload\UploadManagerInterface;
use NeoPHP\Package\Orm\Contract\EntityManagerInterface;
use NeoPHP\Package\Translation\TranslationManagerInterface;

class ProjectFormHelper
{
    public const SCREENSHOT_SLOTS = 4;

    public const FEATURES_MAX = 20;

    public const FEATURE_MAX_LENGTH = 150;

    public const FILE_MAX_SIZE = 5242880;

    public function __construct(
        private EntityManagerInterface $entityManager,
        private UploadManagerInterface $uploader,
        private TranslationManagerInterface $translator,
        private UserPathHelper $paths,
    ) {
    }

    public function options(): array
    {
        return [
            'categories' => $this->entityManager->getRepository(Category::class)->findBy([], ['slug' => 'ASC']),
            'technologies' => $this->entityManager->getRepository(Technology::class)->findBy([], ['name' => 'ASC']),
            'visibilities' => ProjectVisibilityEnum::cases(),
            'statuses' => ProjectStatusEnum::editable(),
            'slots' => self::SCREENSHOT_SLOTS,
        ];
    }

    public function values(?Project $project): array
    {
        if ($project === null) {
            return [
                'name' => '',
                'description' => '',
                'short_description' => '',
                'website_url' => '',
                'repository_url' => '',
                'category' => '',
                'visibility' => ProjectVisibilityEnum::PUBLIC->value,
                'status' => ProjectStatusEnum::DRAFT->value,
                'technologies' => [],
                'features' => [],
            ];
        }

        return [
            'name' => (string) $project->getName(),
            'description' => (string) $project->getDescription(),
            'short_description' => (string) $project->getShortDescription(),
            'website_url' => (string) $project->getWebsiteUrl(),
            'repository_url' => (string) $project->getRepositoryUrl(),
            'category' => $project->getCategory()?->getSlug() ?? '',
            'visibility' => $project->getVisibilityEnum()->value,
            'status' => $project->getStatus()->value,
            'technologies' => array_values(array_map(
                static fn (Technology $technology): string => $technology->getSlug(),
                iterator_to_array($project->getTechnologies())
            )),
            'features' => array_values($project->getFeatures() ?? []),
        ];
    }

    public function valuesFromRequest(array $data): array
    {
        $string = static fn (string $key): string => trim((string) ($data[$key] ?? ''));

        return [
            'name' => $string('name'),
            'description' => $string('description'),
            'short_description' => $string('short_description'),
            'website_url' => $string('website_url'),
            'repository_url' => $string('repository_url'),
            'category' => $string('category'),
            'visibility' => $string('visibility'),
            'status' => $string('status'),
            'technologies' => array_values(array_unique(array_filter(
                array_map(static fn (mixed $value): string => trim((string) $value), (array) ($data['technologies'] ?? [])),
                static fn (string $value): bool => $value !== ''
            ))),
            'features' => array_values(array_filter(
                array_map(static fn (mixed $value): string => trim((string) $value), (array) ($data['features'] ?? [])),
                static fn (string $value): bool => $value !== ''
            )),
        ];
    }

    public function save(?Project $project, User $user, array $values, ?UploadedFile $cover, bool $removeCover, array $screenshots, array $removeScreenshots): array
    {
        $errors = [];

        if ($values['name'] === '' || mb_strlen($values['name']) > 150) {
            $errors['name'] = $this->trans('project_form.errors.name');
        }

        if ($values['description'] === '') {
            $errors['description'] = $this->trans('project_form.errors.description');
        }

        if ($values['short_description'] === '' || mb_strlen($values['short_description']) > 512) {
            $errors['short_description'] = $this->trans('project_form.errors.short_description');
        }

        foreach (['website_url', 'repository_url'] as $key) {
            if ($values[$key] !== '' && !$this->isUrl($values[$key])) {
                $errors[$key] = $this->trans('project_form.errors.url');
            }
        }

        $category = $values['category'] !== ''
            ? $this->entityManager->getRepository(Category::class)->findOneBy(['slug' => $values['category']])
            : null;

        if (!$category instanceof Category) {
            $errors['category'] = $this->trans('project_form.errors.category');
        }

        $visibility = ProjectVisibilityEnum::tryFrom($values['visibility']);
        $status = ProjectStatusEnum::tryFrom($values['status']);

        if ($visibility === null) {
            $errors['visibility'] = $this->trans('project_form.errors.visibility');
        }

        if ($status === null || $status === ProjectStatusEnum::ALL) {
            $errors['status'] = $this->trans('project_form.errors.status');
        }

        $technologies = $this->entityManager->getRepository(Technology::class)->findBySlugs($values['technologies']);

        if ($technologies === [] || count($technologies) !== count($values['technologies'])) {
            $errors['technologies'] = $this->trans('project_form.errors.technologies');
        }

        if (count($values['features']) > self::FEATURES_MAX) {
            $errors['features'] = $this->trans('project_form.errors.features_count');
        }

        foreach ($values['features'] as $feature) {
            if (mb_strlen($feature) > self::FEATURE_MAX_LENGTH) {
                $errors['features'] = $this->trans('project_form.errors.feature_length');
            }
        }

        if ($errors !== []) {
            return ['errors' => $errors, 'project' => $project];
        }

        $isNew = $project === null;
        $slug = $isNew ? $this->uniqueSlug($values['name']) : (string) $project->getSlug();
        $directory = $this->paths->projectDirectory($user, $slug);
        $stored = [];

        try {
            $coverPath = $cover instanceof UploadedFile ? $this->store($cover, $directory, $stored) : null;
        } catch (UploadException) {
            $this->rollback($stored);

            return ['errors' => ['cover' => $this->trans('project_form.errors.image')], 'project' => $project];
        }

        $newScreenshots = [];

        try {
            foreach ($screenshots as $index => $file) {
                $index = (int) $index;

                if ($index >= 0 && $index < self::SCREENSHOT_SLOTS && $file instanceof UploadedFile) {
                    $newScreenshots[$index] = $this->store($file, $directory, $stored);
                }
            }
        } catch (UploadException) {
            $this->rollback($stored);

            return ['errors' => ['screenshots' => $this->trans('project_form.errors.image')], 'project' => $project];
        }

        $project ??= (new Project())->setUser($user)->setSlug($slug);
        $obsolete = [];

        if ($coverPath !== null) {
            $obsolete[] = $project->getCover();
            $project->setCover($coverPath);
        } elseif ($removeCover) {
            $obsolete[] = $project->getCover();
            $project->setCover(null);
        }

        $project
            ->setName($values['name'])
            ->setDescription($values['description'])
            ->setShortDescription($values['short_description'])
            ->setWebsiteUrl($values['website_url'] !== '' ? $values['website_url'] : null)
            ->setRepositoryUrl($values['repository_url'] !== '' ? $values['repository_url'] : null)
            ->setCategory($category)
            ->setVisibility($visibility)
            ->setFeatures($values['features'] !== [] ? $values['features'] : null);

        $this->applyStatus($project, $status);
        $this->syncTechnologies($project, $technologies);
        $obsolete = [...$obsolete, ...$this->syncScreenshots($project, $newScreenshots, $removeScreenshots)];

        if (!$isNew) {
            $project->setUpdatedAt(new \DateTime());
        }

        $this->entityManager->persist($project);
        $this->entityManager->flush();

        $this->rollback(array_filter($obsolete));

        return ['errors' => [], 'project' => $project];
    }

    private function store(UploadedFile $file, string $directory, array &$stored): string
    {
        $path = $this->uploader->store($file, $directory, ['max_size' => self::FILE_MAX_SIZE]);
        $stored[] = $path;

        return $path;
    }

    private function rollback(array $paths): void
    {
        foreach ($paths as $path) {
            $this->uploader->delete($path);
        }
    }

    private function applyStatus(Project $project, ProjectStatusEnum $status): void
    {
        match ($status) {
            ProjectStatusEnum::PUBLISHED => $project
                ->setPublishedAt($project->getPublishedAt() ?? new \DateTime())
                ->setArchivedAt(null),
            ProjectStatusEnum::ARCHIVED => $project
                ->setArchivedAt($project->getArchivedAt() ?? new \DateTime()),
            default => $project
                ->setPublishedAt(null)
                ->setArchivedAt(null),
        };
    }

    private function syncTechnologies(Project $project, array $technologies): void
    {
        $ids = array_map(static fn (Technology $technology): mixed => $technology->getId(), $technologies);

        foreach (iterator_to_array($project->getTechnologies()) as $technology) {
            if (!in_array($technology->getId(), $ids, true)) {
                $project->removeTechnology($technology);
            }
        }

        foreach ($technologies as $technology) {
            $project->addTechnology($technology);
        }
    }

    private function syncScreenshots(Project $project, array $newScreenshots, array $removeScreenshots): array
    {
        $existing = [];
        $obsolete = [];

        foreach (iterator_to_array($project->getScreenshots()) as $screenshot) {
            $existing[$screenshot->getPosition()] = $screenshot;
        }

        $remove = array_map('intval', array_keys(array_filter($removeScreenshots, static fn (mixed $value): bool => (string) $value === '1')));

        for ($index = 0; $index < self::SCREENSHOT_SLOTS; $index++) {
            $current = $existing[$index] ?? null;

            if (isset($newScreenshots[$index])) {
                if ($current !== null) {
                    $obsolete[] = $current->getImage();
                    $current->setImage($newScreenshots[$index]);
                    continue;
                }

                $project->addScreenshot(
                    (new ProjectScreenshot())
                        ->setTitle(mb_substr((string) $project->getName() . ' #' . ($index + 1), 0, 100))
                        ->setDescription('')
                        ->setImage($newScreenshots[$index])
                        ->setPosition($index)
                );

                continue;
            }

            if ($current !== null && in_array($index, $remove, true)) {
                $obsolete[] = $current->getImage();
                $project->removeScreenshot($current);
            }
        }

        return $obsolete;
    }

    private function uniqueSlug(string $name): string
    {
        $base = $this->slugify($name);
        $base = $base !== '' ? $base : 'project';
        $slug = $base;
        $suffix = 2;

        while ($this->entityManager->getRepository(Project::class)->findOneBy(['slug' => $slug]) !== null) {
            $slug = $base . '-' . $suffix++;
        }

        return $slug;
    }

    private function slugify(string $value): string
    {
        if (function_exists('iconv')) {
            $value = (string) iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $value);
        }

        $value = strtolower((string) preg_replace('/[^a-zA-Z0-9]+/', '-', $value));

        return substr(trim($value, '-'), 0, 180);
    }

    private function isUrl(string $url): bool
    {
        return mb_strlen($url) <= 255
            && preg_match('#^https?://#i', $url) === 1
            && filter_var($url, FILTER_VALIDATE_URL) !== false;
    }

    private function trans(string $key): string
    {
        return $this->translator->translate($key);
    }
}