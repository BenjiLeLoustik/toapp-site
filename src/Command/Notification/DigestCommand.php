<?php

declare(strict_types=1);

namespace App\Command\Notification;

use App\Notification\Helper\DigestHelper;
use NeoPHP\Process\Console\Attribute\AsCommand;
use NeoPHP\Process\Console\Contract\AbstractConsole;
use NeoPHP\Process\Console\Contract\InputInterface;
use NeoPHP\Process\Console\Contract\OutputInterface;
use NeoPHP\Process\Console\IO\InputOption;

#[AsCommand(name: 'notification:digest', description: 'Sends the activity summary to the users whose frequency is reached')]
class DigestCommand extends AbstractConsole
{
    public function __construct(
        private DigestHelper $digest,
    ) {
    }

    protected function configure(InputInterface $input, OutputInterface $output): void
    {
        $input->addOption('dry-run', 'd', InputOption::VALUE_NONE, 'Counts the emails without sending them');

        $this->setHelp(implode("\n", [
            'Run it once a day: daily, weekly and monthly summaries are sent when due.',
            '--force sends the summary to every user, whatever the last sending date.',
        ]));

        $this->addExample('app:notification:digest');
        $this->addExample('app:notification:digest --dry-run');
        $this->addExample('app:notification:digest --force');
    }

    protected function interact(InputInterface $input, OutputInterface $output): void
    {
    }

    protected function do(InputInterface $input, OutputInterface $output): int
    {
        $report = $this->digest->run((bool) $input->getOption('dry-run'), (bool) $input->getOption('force'));

        $output->table(['Checked', 'Sent', 'Failed'], [[$report['checked'], $report['sent'], $report['failed']]]);

        return $report['failed'] > 0 ? self::FAILURE : self::SUCCESS;
    }
}