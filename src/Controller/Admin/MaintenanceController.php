<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Admin\Contract\AbstractToolController;
use App\Admin\Helper\MaintenanceHelper;
use App\Command\Admin\MaintenanceCommand;
use DateTimeImmutable;
use NeoPHP\Component\Form\Type\ChoiceType;
use NeoPHP\Component\Form\Type\IntegerType;
use NeoPHP\Component\Http\Response\Response;
use NeoPHP\Component\Routing\Attribute\Route;
use NeoPHP\NeoAdmin\Page\Action;
use NeoPHP\NeoAdmin\Page\Block\Grid;
use NeoPHP\NeoAdmin\Page\Column;

class MaintenanceController extends AbstractToolController
{
    public const PREVIEW = 50;

    #[Route('/admin/maintenance', name: 'admin_maintenance', methods: ['GET', 'POST'])]
    public function index(): Response
    {
        $this->denyUnlessAdmin();
        $maintenance = $this->get(MaintenanceHelper::class);
        $orphans = $maintenance->orphanUploads();
        $size = array_sum(array_column($orphans, 'size'));
        $counts = [];

        foreach (MaintenanceCommand::DEFAULT_MONTHS as $target => $months) {
            $counts[$target] = $maintenance->countBefore(MaintenanceHelper::targets()[$target], $this->before($months));
        }

        $form = $this->createAdminForm('maintenance_purge', ['target' => 'views', 'months' => MaintenanceCommand::DEFAULT_MONTHS['views']], [
            'target' => [ChoiceType::class, ['label' => 'Data', 'choices' => ['Project views' => 'views', 'Login history' => 'logins']]],
            'months' => [IntegerType::class, ['label' => 'Keep the last N months', 'attr' => ['min' => 1]]],
        ]);

        if (($data = $this->submitted($form)) !== null) {
            $months = max(1, (int) $data['months']);
            $target = (string) $data['target'];
            $deleted = $maintenance->purgeBefore(MaintenanceHelper::targets()[$target], $this->before($months));
            $this->addFlash('success', sprintf('%d %s older than %d months deleted.', $deleted, $target, $months));

            return $this->redirectToRoute('admin_maintenance');
        }

        $page = $this->page('Maintenance')
            ->description('Cleans the unused uploads and the old statistics.')
            ->alert('Purging the views changes the statistics of the "All" period.', 'warning')
            ->grid(3, fn (Grid $grid) => $grid
                ->stat('Orphan uploads', count($orphans), 'folder', null, $this->size($size))
                ->stat('Old project views', $counts['views'], 'eye', null, sprintf('Older than %d months', MaintenanceCommand::DEFAULT_MONTHS['views']))
                ->stat('Old login history', $counts['logins'], 'user', null, sprintf('Older than %d months', MaintenanceCommand::DEFAULT_MONTHS['logins'])));

        foreach (['uploads' => count($orphans), 'views' => $counts['views'], 'logins' => $counts['logins']] as $action => $count) {
            if ($count > 0) {
                $page->action(Action::post(
                    sprintf('Clean the %s (%d)', $action, $count),
                    $this->generateUrl('admin_maintenance_clean', ['action' => $action]),
                    'maintenance_' . $action,
                    sprintf('Delete %d %s? This cannot be undone.', $count, $action === 'uploads' ? 'file(s)' : $action),
                    'trash',
                )->danger());
            }
        }

        return $page
            ->table(
                count($orphans) > self::PREVIEW ? sprintf('Orphan uploads (first %d of %d)', self::PREVIEW, count($orphans)) : 'Orphan uploads',
                array_slice(array_map(fn (array $orphan): array => ['path' => $orphan['path'], 'size' => $this->size((int) $orphan['size'])], $orphans), 0, self::PREVIEW),
                [Column::code('path', 'File'), Column::text('size', 'Size')],
                'folder',
                'No orphan upload.',
            )
            ->form($form, 'Purge', null, 'Purge with a custom period')
            ->render($this->status($form));
    }

    #[Route('/admin/maintenance/{action}', name: 'admin_maintenance_clean', methods: ['POST'], requirements: ['action' => 'uploads|views|logins'])]
    public function clean(string $action): Response
    {
        $this->denyUnlessAdmin();
        $maintenance = $this->get(MaintenanceHelper::class);

        $this->handlePost(
            'maintenance_' . $action,
            fn (): int => $action === 'uploads'
                ? $maintenance->deleteUploads($maintenance->orphanUploads())
                : $maintenance->purgeBefore(MaintenanceHelper::targets()[$action], $this->before(MaintenanceCommand::DEFAULT_MONTHS[$action])),
            static fn (int $deleted): string => sprintf('%d %s deleted.', $deleted, $action === 'uploads' ? 'file(s)' : $action),
        );

        return $this->redirectToRoute('admin_maintenance');
    }

    protected function before(int $months): DateTimeImmutable
    {
        return (new DateTimeImmutable())->modify('-' . $months . ' months');
    }

    protected function size(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $index = 0;
        $value = (float) $bytes;

        while ($value >= 1024 && $index < count($units) - 1) {
            $value /= 1024;
            $index++;
        }

        return round($value, 1) . ' ' . $units[$index];
    }
}