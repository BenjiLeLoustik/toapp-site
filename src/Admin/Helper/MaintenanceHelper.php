<?php

declare(strict_types=1);

namespace App\Admin\Helper;

use App\Entity\Project;
use App\Entity\ProjectScreenshot;
use App\Entity\ProjectView;
use App\Entity\User;
use App\Entity\UserLoginHistory;
use App\User\Helper\UserPathHelper;
use NeoPHP\Component\Upload\Contract\UploaderInterface;
use NeoPHP\Package\Orm\Contract\EntityManagerInterface;

class MaintenanceHelper
{
    public const BATCH_SIZE = 500;

    public function __construct(
        private EntityManagerInterface $entityManager,
        private UploaderInterface $uploader,
    ) {
    }

    public function orphanUploads(): array
    {
        $root = $this->uploader->path(UserPathHelper::ROOT);

        if (!is_dir($root)) {
            return [];
        }

        $referenced = array_flip($this->referencedUploads());
        $base = rtrim(str_replace('\\', '/', $this->uploader->getUploadPath()), '/') . '/';
        $orphans = [];

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($root, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::LEAVES_ONLY
        );

        foreach ($iterator as $file) {
            if (!$file instanceof \SplFileInfo || !$file->isFile()) {
                continue;
            }

            $relative = substr(str_replace('\\', '/', $file->getPathname()), strlen($base));

            if (!isset($referenced[$relative])) {
                $orphans[] = ['path' => $relative, 'size' => $file->getSize()];
            }
        }

        return $orphans;
    }

    public function deleteUploads(array $orphans): int
    {
        $deleted = 0;

        foreach ($orphans as $orphan) {
            if ($this->uploader->delete($orphan['path'])) {
                $deleted++;
            }
        }

        $this->removeEmptyDirectories($this->uploader->path(UserPathHelper::ROOT));

        return $deleted;
    }

    public function countBefore(string $class, \DateTimeImmutable $before): int
    {
        return (int) $this->entityManager->getRepository($class)->createQueryBuilder('x')
            ->select('COUNT(x.id) AS total')
            ->where('x.createdAt < :before')
            ->setParameter('before', $before->format('Y-m-d H:i:s'))
            ->getSingleScalarResult();
    }

    public function purgeBefore(string $class, \DateTimeImmutable $before, ?callable $progress = null): int
    {
        $deleted = 0;

        do {
            $entities = $this->entityManager->getRepository($class)->createQueryBuilder('x')
                ->where('x.createdAt < :before')
                ->setParameter('before', $before->format('Y-m-d H:i:s'))
                ->setMaxResults(self::BATCH_SIZE)
                ->getResult();

            foreach ($entities as $entity) {
                $this->entityManager->remove($entity);
            }

            $this->entityManager->flush();
            $deleted += count($entities);

            if ($progress !== null && $entities !== []) {
                $progress(count($entities));
            }
        } while (count($entities) === self::BATCH_SIZE);

        return $deleted;
    }

    public static function targets(): array
    {
        return [
            'views' => ProjectView::class,
            'logins' => UserLoginHistory::class,
        ];
    }

    private function referencedUploads(): array
    {
        $paths = [];

        foreach ([
                     [User::class, 'avatar'],
                     [Project::class, 'cover'],
                     [ProjectScreenshot::class, 'image'],
                 ] as [$class, $field]) {
            $rows = $this->entityManager->getRepository($class)->createQueryBuilder('x')
                ->select('x.' . $field . ' AS path')
                ->where('x.' . $field . ' IS NOT NULL')
                ->getScalarResult();

            foreach ($rows as $row) {
                $paths[] = ltrim(str_replace('\\', '/', (string) $row['path']), '/');
            }
        }

        return $paths;
    }

    private function removeEmptyDirectories(string $directory): void
    {
        if (!is_dir($directory)) {
            return;
        }

        foreach (scandir($directory) ?: [] as $entry) {
            if ($entry === '.' || $entry === '..') {
                continue;
            }

            $path = $directory . DIRECTORY_SEPARATOR . $entry;

            if (is_dir($path)) {
                $this->removeEmptyDirectories($path);

                if (count(scandir($path) ?: []) === 2) {
                    rmdir($path);
                }
            }
        }
    }
}