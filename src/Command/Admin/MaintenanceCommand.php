<?php

declare(strict_types=1);

namespace App\Command\Admin;

use App\Admin\Helper\MaintenanceHelper;
use NeoPHP\Process\Console\Attribute\AsCommand;
use NeoPHP\Process\Console\Contract\AbstractConsole;
use NeoPHP\Process\Console\Contract\InputInterface;
use NeoPHP\Process\Console\Contract\OutputInterface;
use NeoPHP\Process\Console\Exception\InvalidInputException;
use NeoPHP\Process\Console\IO\InputArgument;
use NeoPHP\Process\Console\IO\InputOption;

#[AsCommand(name: 'admin:maintenance', description: 'Cleans the uploads and the old statistics', aliases: ['maintenance'])]
class MaintenanceCommand extends AbstractConsole
{
    public const ACTIONS = ['uploads', 'views', 'logins'];

    public const DEFAULT_MONTHS = ['views' => 24, 'logins' => 6];

    public function __construct(
        private MaintenanceHelper $maintenance,
    ) {
    }

    protected function configure(InputInterface $input, OutputInterface $output): void
    {
        $input->addArgument('action', InputArgument::REQUIRED, implode(', ', self::ACTIONS));
        $input->addOption('months', 'm', InputOption::VALUE_REQUIRED, 'Keep the last N months (views: 24, logins: 6)');
        $input->addOption('dry-run', null, InputOption::VALUE_NONE, 'Only display what would be deleted');

        $this->setHelp(implode("\n", [
            'uploads               deletes the files of public/uploads/user/ that are not used anymore',
            'views  [--months=24]  deletes the project views older than N months',
            'logins [--months=6]   deletes the login history older than N months',
            '',
            'Warning: purging the views changes the statistics of the "All" period.',
        ]));

        $this->addExample('app:maintenance uploads --dry-run');
        $this->addExample('app:maintenance views --months=12 --force');
        $this->addExample('app:maintenance logins');
    }

    protected function interact(InputInterface $input, OutputInterface $output): void
    {
        if (!$input->isArgumentProvided('action')) {
            $input->setArgument('action', $output->choice('Action', self::ACTIONS, 'uploads'));
        }
    }

    protected function do(InputInterface $input, OutputInterface $output): int
    {
        $action = (string) $input->getArgument('action');
        $dryRun = (bool) $input->getOption('dry-run');

        if (!in_array($action, self::ACTIONS, true)) {
            throw new InvalidInputException(sprintf('Unknown action "%s": use %s.', $action, implode(', ', self::ACTIONS)));
        }

        return $action === 'uploads'
            ? $this->uploads($input, $output, $dryRun)
            : $this->purge($input, $output, $action, $dryRun);
    }

    private function uploads(InputInterface $input, OutputInterface $output, bool $dryRun): int
    {
        $orphans = $this->maintenance->orphanUploads();

        if ($orphans === []) {
            $output->success('No orphan upload.');

            return self::SUCCESS;
        }

        $size = array_sum(array_column($orphans, 'size'));

        if ($output->isVerbose() || $dryRun) {
            $output->listing(array_column($orphans, 'path'));
        }

        $output->text(sprintf('%d orphan file(s), %s.', count($orphans), $this->size($size)));

        if ($dryRun) {
            return self::SUCCESS;
        }

        if (!$input->getOption('force') && !$output->confirm('Delete these files?', false)) {
            return self::SUCCESS;
        }

        $output->success(sprintf('%d file(s) deleted.', $this->maintenance->deleteUploads($orphans)));

        return self::SUCCESS;
    }

    private function purge(InputInterface $input, OutputInterface $output, string $action, bool $dryRun): int
    {
        $months = (int) ($input->getOption('months') ?? self::DEFAULT_MONTHS[$action]);

        if ($months < 1) {
            throw new InvalidInputException('--months must be at least 1.');
        }

        $class = MaintenanceHelper::targets()[$action];
        $before = (new \DateTimeImmutable())->modify('-' . $months . ' months');
        $count = $this->maintenance->countBefore($class, $before);

        $output->text(sprintf('%d %s older than %s (%d months).', $count, $action, $before->format('Y-m-d'), $months));

        if ($count === 0 || $dryRun) {
            return self::SUCCESS;
        }

        if (!$input->getOption('force') && !$output->confirm(sprintf('Delete these %d %s?', $count, $action), false)) {
            return self::SUCCESS;
        }

        $output->progressStart($count);
        $deleted = $this->maintenance->purgeBefore($class, $before, $output->progressAdvance(...));
        $output->progressFinish();

        $output->success(sprintf('%d %s deleted.', $deleted, $action));

        return self::SUCCESS;
    }

    private function size(int $bytes): string
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