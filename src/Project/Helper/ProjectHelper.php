<?php

declare(strict_types=1);

namespace App\Project\Helper;

use App\Entity\Project;
use App\Entity\ProjectFavorite;
use App\Entity\ProjectLike;
use App\Entity\ProjectShare;
use App\Entity\ProjectView;
use App\Entity\User;
use App\Project\Enum\ProjectShareEnum;
use App\Project\Enum\ProjectStatusEnum;
use App\Project\Enum\ProjectViewSourceEnum;
use App\Project\Enum\ProjectVisibilityEnum;
use NeoPHP\Component\Http\Request\Request;
use NeoPHP\Package\Orm\Contract\EntityManagerInterface;

class ProjectHelper
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private VisitorCountryResolver $countryResolver,
    ) {
    }

    public function validateProject(int $projectId, string $projectSlug, ?User $user = null): ?Project
    {
        /** @var null|Project $project */
        $project = $this->entityManager->getRepository(Project::class)->find($projectId);

        if ($project === null || $project->getSlug() !== $projectSlug || !$this->canView($project, $user)) {
            return null;
        }

        return $project;
    }

    public function isOwner(Project $project, ?User $user): bool
    {
        return $user !== null && $project->getUser()?->getId() === $user->getId();
    }

    public function canView(Project $project, ?User $user): bool
    {
        if ($this->isOwner($project, $user)) {
            return true;
        }

        return $project->getStatus() === ProjectStatusEnum::PUBLISHED
            && $project->getVisibilityEnum() === ProjectVisibilityEnum::PUBLIC;
    }

    public function links(Project $project): array
    {
        $links = array_filter([
            $project->getWebsiteUrl(),
            $project->getRepositoryUrl(),
            ...($project->getExternalLinks() ?? []),
        ], static fn (mixed $link): bool => is_string($link) && $link !== '');

        return array_values(array_unique($links));
    }

    public function addView(Project $project, ?User $user, Request $request): void
    {
        if ($this->isOwner($project, $user)) {
            return;
        }

        $projectViewRepository = $this->entityManager->getRepository(ProjectView::class);

        if ($user !== null && $projectViewRepository->existsForUser($project, $user)) {
            return;
        }

        $view = new ProjectView();
        $view
            ->setProject($project)
            ->setUser($user)
            ->setIp($request->getClientIp())
            ->setSource(ProjectViewSourceEnum::fromReferer(
                $request->headers->get('Referer'),
                (string) parse_url($request->getUri(), PHP_URL_HOST)
            ))
            ->setCountry($this->countryResolver->resolve($request));

        $this->entityManager->persist($view);
        $this->entityManager->flush();
    }

    public function addShare(Project $project, ProjectShareEnum $type, ?User $user, ?string $ip): void
    {
        $share = new ProjectShare();
        $share->setProject($project);
        $share->setType($type);
        $share->setUser($user);
        $share->setIp($ip);

        $this->entityManager->persist($share);
        $this->entityManager->flush();
    }

    public function isLiked(Project $project, ?User $user): bool
    {
        if ($user === null) {
            return false;
        }

        return $this->entityManager->getRepository(ProjectLike::class)->findOneBy([
                'project' => $project,
                'user' => $user,
            ]) !== null;
    }

    public function toggleLike(Project $project, User $user): bool
    {
        $like = $this->entityManager->getRepository(ProjectLike::class)->findOneBy([
            'project' => $project,
            'user' => $user,
        ]);

        if ($like !== null) {
            $this->entityManager->remove($like);
            $this->entityManager->flush();

            return false;
        }

        $like = new ProjectLike();
        $like->setProject($project);
        $like->setUser($user);

        $this->entityManager->persist($like);
        $this->entityManager->flush();

        return true;
    }

    public function countLikes(Project $project): int
    {
        return $this->entityManager->getRepository(ProjectLike::class)->count(['project' => $project]);
    }

    public function isFavorite(Project $project, ?User $user): bool
    {
        if ($user === null) {
            return false;
        }

        return $this->entityManager->getRepository(ProjectFavorite::class)->findOneBy([
                'project' => $project,
                'user' => $user,
            ]) !== null;
    }

    public function toggleFavorite(Project $project, User $user): bool
    {
        $favorite = $this->entityManager->getRepository(ProjectFavorite::class)->findOneBy([
            'project' => $project,
            'user' => $user,
        ]);

        if ($favorite !== null) {
            $this->entityManager->remove($favorite);
            $this->entityManager->flush();

            return false;
        }

        $favorite = new ProjectFavorite();
        $favorite->setProject($project);
        $favorite->setUser($user);

        $this->entityManager->persist($favorite);
        $this->entityManager->flush();

        return true;
    }
}