<?php

declare(strict_types=1);

namespace App\Command\Notification;

use App\Notification\Helper\ActivityNotificationHelper;
use NeoPHP\Process\Console\Attribute\AsCommand;
use NeoPHP\Process\Console\Contract\AbstractConsole;
use NeoPHP\Process\Console\Contract\InputInterface;
use NeoPHP\Process\Console\Contract\OutputInterface;
use NeoPHP\Process\Console\IO\InputOption;

#[AsCommand(name: 'notification:activity', description: 'Sends the hourly email of new likes, shares and favorites')]
class ActivityCommand extends AbstractConsole
{
    public function __construct(
        private ActivityNotificationHelper $activity,
    ) {
    }

    protected function configure(InputInterface $input, OutputInterface $output): void
    {
        $input->addOption('dry-run', 'd', InputOption::VALUE_NONE, 'Counts the emails without sending them');

        $this->setHelp('Run it every hour: only the users with a new activity since the last run receive an email.');
        $this->addExample('app:notification:activity');
        $this->addExample('app:notification:activity --dry-run');
    }

    protected function interact(InputInterface $input, OutputInterface $output): void
    {
    }

    protected function do(InputInterface $input, OutputInterface $output): int
    {
        $report = $this->activity->run((bool) $input->getOption('dry-run'));

        $output->table(['Checked', 'Sent', 'Failed'], [[$report['checked'], $report['sent'], $report['failed']]]);

        return $report['failed'] > 0 ? self::FAILURE : self::SUCCESS;
    }
}