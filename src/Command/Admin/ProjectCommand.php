<?php

declare(strict_types=1);

namespace App\Command\Admin;

use App\Admin\Exception\AdminException;
use App\Admin\Helper\ProjectAdminHelper;
use App\Admin\Helper\UserAdminHelper;
use App\Project\Enum\ProjectStatusEnum;
use NeoPHP\Process\Console\Attribute\AsCommand;
use NeoPHP\Process\Console\Contract\AbstractConsole;
use NeoPHP\Process\Console\Contract\InputInterface;
use NeoPHP\Process\Console\Contract\OutputInterface;
use NeoPHP\Process\Console\Exception\InvalidInputException;
use NeoPHP\Process\Console\IO\InputArgument;
use NeoPHP\Process\Console\IO\InputOption;

#[AsCommand(name: 'app:project', description: 'Moderates the projects', aliases: ['project'])]
class ProjectCommand extends AbstractConsole
{
    public const ACTIONS = ['list', 'show', 'archive', 'unpublish', 'delete'];

    public function __construct(
        private ProjectAdminHelper $projects,
        private UserAdminHelper $users,
    ) {
    }

    protected function configure(InputInterface $input, OutputInterface $output): void
    {
        $statuses = array_map(static fn (ProjectStatusEnum $status): string => $status->value, ProjectStatusEnum::cases());

        $input->addArgument('action', InputArgument::REQUIRED, implode(', ', self::ACTIONS));
        $input->addArgument('id', InputArgument::OPTIONAL, 'Id of the project');
        $input->addOption('user', 'u', InputOption::VALUE_REQUIRED, 'Owner (id, email, username or slug) (list)');
        $input->addOption('status', 's', InputOption::VALUE_REQUIRED, implode(', ', $statuses) . ' (list)', ProjectStatusEnum::ALL->value);
        $input->addOption('limit', 'l', InputOption::VALUE_REQUIRED, 'Maximum number of results (list)', '50');

        $this->setHelp(implode("\n", [
            'list [--user=] [--status=] [--limit=]  lists the projects, the most recent first',
            'show <id>                              displays a project',
            'archive <id>                           archives a project (hidden from the public pages)',
            'unpublish <id>                         puts a published project back to draft',
            'delete <id>                            deletes a project and its files (irreversible)',
        ]));

        $this->addExample('app:project list --status=published');
        $this->addExample('app:project list --user=@lucasdev');
        $this->addExample('app:project archive 12');
        $this->addExample('app:project delete 12 --force');
    }

    protected function interact(InputInterface $input, OutputInterface $output): void
    {
        if (!$input->isArgumentProvided('action')) {
            $input->setArgument('action', $output->choice('Action', self::ACTIONS, 'list'));
        }

        if ($input->getArgument('action') !== 'list' && !$input->isArgumentProvided('id')) {
            $input->setArgument('id', $output->ask('Id of the project', null, static fn (?string $value): string => ctype_digit((string) $value)
                ? (string) $value
                : throw new \RuntimeException('The id must be a number.')));
        }
    }

    protected function do(InputInterface $input, OutputInterface $output): int
    {
        $action = (string) $input->getArgument('action');

        if (!in_array($action, self::ACTIONS, true)) {
            throw new InvalidInputException(sprintf('Unknown action "%s": use %s.', $action, implode(', ', self::ACTIONS)));
        }

        try {
            if ($action === 'list') {
                return $this->list($input, $output);
            }

            $id = (string) $input->getArgument('id');

            if (!ctype_digit($id)) {
                throw new InvalidInputException('The id of the project is missing or not a number.');
            }

            $project = $this->projects->get((int) $id);
            $label = sprintf('#%d "%s"', $project->getId(), $project->getName());

            if ($action === 'show') {
                $output->definitionList($this->projects->summary($project));

                return self::SUCCESS;
            }

            if (!$input->getOption('force') && !$output->confirm(sprintf('%s the project %s?', ucfirst($action), $label), false)) {
                return self::SUCCESS;
            }

            match ($action) {
                'archive' => $this->projects->archive($project),
                'unpublish' => $this->projects->unpublish($project),
                'delete' => $this->projects->delete($project),
            };

            $output->success(sprintf('Action "%s" done on the project %s.', $action, $label));
        } catch (AdminException $exception) {
            $output->error($exception->getMessage());

            return self::FAILURE;
        }

        return self::SUCCESS;
    }

    private function list(InputInterface $input, OutputInterface $output): int
    {
        $status = ProjectStatusEnum::tryFrom((string) $input->getOption('status'));

        if ($status === null) {
            throw new InvalidInputException(sprintf('Unknown status "%s".', $input->getOption('status')));
        }

        $user = $input->getOption('user') !== null ? $this->users->find((string) $input->getOption('user')) : null;

        $rows = array_map(
            static fn (array $project): array => array_values($project),
            $this->projects->list($user, $status, max(1, (int) $input->getOption('limit')))
        );

        $rows === []
            ? $output->note('No project found.')
            : $output->table(['ID', 'Name', 'Slug', 'Owner', 'Status', 'Visibility', 'Views', 'Likes', 'Created'], $rows);

        return self::SUCCESS;
    }
}