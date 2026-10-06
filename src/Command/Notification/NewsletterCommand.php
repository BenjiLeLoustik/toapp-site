<?php

declare(strict_types=1);

namespace App\Command\Notification;

use App\Notification\Helper\NewsletterHelper;
use NeoPHP\Process\Console\Attribute\AsCommand;
use NeoPHP\Process\Console\Contract\AbstractConsole;
use NeoPHP\Process\Console\Contract\InputInterface;
use NeoPHP\Process\Console\Contract\OutputInterface;
use NeoPHP\Process\Console\Exception\InvalidInputException;
use NeoPHP\Process\Console\IO\InputArgument;
use NeoPHP\Process\Console\IO\InputOption;

#[AsCommand(name: 'notification:newsletter:send', description: 'Sends a newsletter to the subscribed users')]
class NewsletterCommand extends AbstractConsole
{
    public function __construct(
        private NewsletterHelper $newsletter,
    ) {
    }

    protected function configure(InputInterface $input, OutputInterface $output): void
    {
        $input->addArgument('subject', InputArgument::REQUIRED, 'Subject of the email');
        $input->addArgument('template', InputArgument::REQUIRED, 'Template in ' . NewsletterHelper::TEMPLATE_DIRECTORY);
        $input->addOption('to', 't', InputOption::VALUE_REQUIRED, 'Sends only to this email (test)');
        $input->addOption('dry-run', 'd', InputOption::VALUE_NONE, 'Counts the recipients without sending');

        $this->setHelp('The template must extend emails/_layout.html.twig and be stored in ' . NewsletterHelper::TEMPLATE_DIRECTORY . '.');
        $this->addExample('app:newsletter:send "What\'s new in October" emails/newsletter/2026-10.html.twig --to=me@example.com');
        $this->addExample('app:newsletter:send "What\'s new in October" emails/newsletter/2026-10.html.twig --force');
    }

    protected function interact(InputInterface $input, OutputInterface $output): void
    {
        if (!$input->isArgumentProvided('subject')) {
            $input->setArgument('subject', $output->ask('Subject', null, $this->required(...)));
        }

        if (!$input->isArgumentProvided('template')) {
            $input->setArgument('template', $output->ask('Template', NewsletterHelper::TEMPLATE_DIRECTORY, $this->required(...)));
        }
    }

    protected function do(InputInterface $input, OutputInterface $output): int
    {
        $subject = trim((string) $input->getArgument('subject'));
        $template = trim((string) $input->getArgument('template'));
        $to = $input->getOption('to');
        $dryRun = (bool) $input->getOption('dry-run');

        if (!$this->newsletter->isValidTemplate($template)) {
            throw new InvalidInputException(sprintf('The template must be a .html.twig file in "%s".', NewsletterHelper::TEMPLATE_DIRECTORY));
        }

        $users = $this->newsletter->recipients($to !== null && $to !== '' ? (string) $to : null);

        if ($users === []) {
            $output->note('No recipient found.');

            return self::SUCCESS;
        }

        if (!$dryRun
            && !$input->getOption('force')
            && !$output->confirm(sprintf('Send "%s" to %d user(s)?', $subject, count($users)), false)) {
            return self::SUCCESS;
        }

        $report = $this->newsletter->send($users, $subject, $template, $dryRun);

        $output->table(['Recipients', 'Sent', 'Failed'], [[$report['checked'], $report['sent'], $report['failed']]]);

        return $report['failed'] > 0 ? self::FAILURE : self::SUCCESS;
    }

    private function required(?string $value): string
    {
        $value = trim((string) $value);

        return $value !== '' ? $value : throw new \RuntimeException('This value is required.');
    }
}