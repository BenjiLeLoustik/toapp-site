<?php

declare(strict_types=1);

namespace App\Command\Admin;

use App\Admin\Exception\AdminException;
use App\Admin\Helper\UserAdminHelper;
use NeoPHP\Process\Console\Attribute\AsCommand;
use NeoPHP\Process\Console\Contract\AbstractConsole;
use NeoPHP\Process\Console\Contract\InputInterface;
use NeoPHP\Process\Console\Contract\OutputInterface;
use NeoPHP\Process\Console\Exception\InvalidInputException;
use NeoPHP\Process\Console\IO\InputArgument;
use NeoPHP\Process\Console\IO\InputOption;

#[AsCommand(name: 'admin:user', description: 'Manages the user accounts', aliases: ['user'])]
class UserCommand extends AbstractConsole
{
    public const ACTIONS = ['search', 'show', 'role-add', 'role-remove', 'certify', 'uncertify', 'deactivate', 'reactivate', 'anonymize'];

    public const DESTRUCTIVE = ['deactivate', 'anonymize'];

    public function __construct(
        private UserAdminHelper $users,
    ) {
    }

    protected function configure(InputInterface $input, OutputInterface $output): void
    {
        $input->addArgument('action', InputArgument::REQUIRED, implode(', ', self::ACTIONS));
        $input->addArgument('user', InputArgument::OPTIONAL, 'User id, email, username or slug (search: text to find)');
        $input->addArgument('role', InputArgument::OPTIONAL, 'Role (role-add, role-remove), e.g. ROLE_ADMIN');
        $input->addOption('limit', 'l', InputOption::VALUE_REQUIRED, 'Maximum number of results (search)', '20');

        $this->setHelp(implode("\n", [
            'search [text]              finds users by email, username, first name or last name',
            'show <user>                displays a user',
            'role-add <user> <role>     gives a role (ROLE_ is optional: admin = ROLE_ADMIN)',
            'role-remove <user> <role>  removes a role',
            'certify / uncertify <user> gives or removes the certified badge',
            'deactivate / reactivate    hides the account until it is reactivated',
            'anonymize <user>           deletes the personal data of the account (irreversible)',
        ]));

        $this->addExample('app:user search lucas');
        $this->addExample('app:user role-add lucas@example.com admin');
        $this->addExample('app:user certify @lucasdev');
        $this->addExample('app:user deactivate 42 --force');
    }

    protected function interact(InputInterface $input, OutputInterface $output): void
    {
        if (!$input->isArgumentProvided('action')) {
            $input->setArgument('action', $output->choice('Action', self::ACTIONS, 'search'));
        }

        $action = (string) $input->getArgument('action');

        if (!$input->isArgumentProvided('user')) {
            $input->setArgument('user', $output->ask(
                $action === 'search' ? 'Text to find (empty for all)' : 'User (id, email, username or slug)',
                $action === 'search' ? '' : null,
                $action === 'search' ? null : $this->required(...)
            ));
        }

        if (in_array($action, ['role-add', 'role-remove'], true) && !$input->isArgumentProvided('role')) {
            $input->setArgument('role', $output->ask('Role (e.g. ROLE_ADMIN)', 'ROLE_ADMIN', $this->required(...)));
        }
    }

    protected function do(InputInterface $input, OutputInterface $output): int
    {
        $action = (string) $input->getArgument('action');
        $identifier = trim((string) $input->getArgument('user'));

        if (!in_array($action, self::ACTIONS, true)) {
            throw new InvalidInputException(sprintf('Unknown action "%s": use %s.', $action, implode(', ', self::ACTIONS)));
        }

        try {
            if ($action === 'search') {
                return $this->search($output, $identifier, max(1, (int) $input->getOption('limit')));
            }

            if ($identifier === '') {
                throw new InvalidInputException('The user is missing.');
            }

            $user = $this->users->find($identifier);
            $label = sprintf('%s (%s)', $user->getUsername(), $user->getEmail());

            if (in_array($action, self::DESTRUCTIVE, true)
                && !$input->getOption('force')
                && !$output->confirm(sprintf('%s the account %s?', ucfirst($action), $label), false)) {
                return self::SUCCESS;
            }

            match ($action) {
                'show' => $output->definitionList($this->users->summary($user)),
                'role-add' => $this->users->addRole($user, (string) $input->getArgument('role')),
                'role-remove' => $this->users->removeRole($user, (string) $input->getArgument('role')),
                'certify' => $this->users->certify($user, true),
                'uncertify' => $this->users->certify($user, false),
                'deactivate' => $this->users->deactivate($user),
                'reactivate' => $this->users->reactivate($user),
                'anonymize' => $this->users->anonymize($user),
            };

            if ($action !== 'show') {
                $output->success(sprintf('Action "%s" done on %s.', $action, $label));
                $output->definitionList($this->users->summary($user));
            }
        } catch (AdminException $exception) {
            $output->error($exception->getMessage());

            return self::FAILURE;
        }

        return self::SUCCESS;
    }

    private function search(OutputInterface $output, string $term, int $limit): int
    {
        $rows = array_map(
            static fn (array $user): array => [$user['id'], $user['name'], $user['username'], $user['email'], $user['roles'], $user['certified'], $user['status'], $user['projects']],
            $this->users->search($term, $limit)
        );

        $rows === []
            ? $output->note('No user found.')
            : $output->table(['ID', 'Name', 'Username', 'Email', 'Roles', 'Certified', 'Status', 'Projects'], $rows);

        return self::SUCCESS;
    }

    private function required(?string $value): string
    {
        $value = trim((string) $value);

        return $value !== '' ? $value : throw new \RuntimeException('This value is required.');
    }
}