<?php

declare(strict_types=1);

namespace App\Project\Helper;

use App\Entity\Project;
use App\Entity\User;
use NeoPHP\Component\Upload\UploadManagerInterface;
use NeoPHP\Package\Orm\Contract\EntityManagerInterface;
use NeoPHP\Package\Translation\TranslationManagerInterface;

class ProjectOwnerHelper
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private UploadManagerInterface $uploader,
        private TranslationManagerInterface $translator,
    ) {
    }

    public function getStats(User $user): array
    {
        $stats = ['projects' => 0, 'likes' => 0, 'views' => 0, 'shares' => 0];

        foreach ($user->getProjects() as $project) {
            $stats['projects']++;
            $stats['likes'] += count($project->getLikes());
            $stats['views'] += count($project->getViews());
            $stats['shares'] += count($project->getShares());
        }

        return $stats;
    }

    public function findOwned(User $user, int $id, string $slug): ?Project
    {
        $project = $this->entityManager->getRepository(Project::class)->findOneBy(['id' => $id, 'slug' => $slug]);

        if (!$project instanceof Project || $project->getUser()?->getId() !== $user->getId()) {
            return null;
        }

        return $project;
    }

    public function validateDeletion(Project $project, array $data): array
    {
        $errors = [];
        $confirmation = trim((string) ($data['confirmation'] ?? ''));

        if (mb_strtolower($confirmation) !== mb_strtolower((string) $project->getName())) {
            $errors['confirmation'] = $this->translator->translate('danger.errors.confirmation');
        }

        if (!in_array($data['acknowledge'] ?? '0', ['1', 'on', 'true'], true)) {
            $errors['acknowledge'] = $this->translator->translate('danger.errors.acknowledge');
        }

        return $errors;
    }

    public function delete(Project $project): void
    {
        $this->uploader->delete($project->getCover());

        foreach ($project->getScreenshots() as $screenshot) {
            $this->uploader->delete($screenshot->getImage());
        }

        $this->entityManager->remove($project);
        $this->entityManager->flush();
    }
}