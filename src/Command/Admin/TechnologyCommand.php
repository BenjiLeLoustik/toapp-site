<?php

declare(strict_types=1);

namespace App\Command\Admin;

use App\Admin\Exception\AdminException;
use App\Admin\Helper\TechnologyAdminHelper;
use NeoPHP\Process\Console\Attribute\AsCommand;
use NeoPHP\Process\Console\Contract\AbstractConsole;
use NeoPHP\Process\Console\Contract\InputInterface;
use NeoPHP\Process\Console\Contract\OutputInterface;
use NeoPHP\Process\Console\Exception\InvalidInputException;
use NeoPHP\Process\Console\IO\InputArgument;
use NeoPHP\Process\Console\IO\InputOption;

#[AsCommand(name: 'admin:technology', description: 'Manages the technologies catalog', aliases: ['technology'])]
class TechnologyCommand extends AbstractConsole
{
    public const ACTIONS = ['list', 'add', 'rename', 'delete', 'merge'];

    public function __construct(
        private TechnologyAdminHelper $technologies,
    ) {
    }

    protected function configure(InputInterface $input, OutputInterface $output): void
    {
        $input->addArgument('action', InputArgument::REQUIRED, 'list, add, rename, delete or merge');
        $input->addArgument('values', InputArgument::OPTIONAL | InputArgument::IS_ARRAY, 'Values of the action (name, slug, target...)');
        $input->addOption('slug', null, InputOption::VALUE_REQUIRED, 'Custom slug (add, rename)');

        $this->setHelp(implode("\n", [
            'list                      lists the technologies and the number of projects using them',
            'add <name>                adds a technology (the slug is generated from the name)',
            'rename <slug> <name>      renames a technology (--slug to change the slug too)',
            'delete <slug>             deletes an unused technology (--force detaches it from its projects)',
            'merge <source> <target>   moves the projects of <source> to <target> and deletes <source>',
        ]));

        $this->addExample('app:technology list');
        $this->addExample('app:technology add "Vue.js"');
        $this->addExample('app:technology rename vuejs "Vue" --slug=vue');
        $this->addExample('app:technology merge reactjs react');
    }

    protected function interact(InputInterface $input, OutputInterface $output): void
    {
        if (!$input->isArgumentProvided('action')) {
            $input->setArgument('action', $output->choice('Action', self::ACTIONS, 'list'));
        }

        if ($input->isArgumentProvided('values')) {
            return;
        }

        $values = match ((string) $input->getArgument('action')) {
            'add' => [$output->ask('Name of the technology', null, $this->required(...))],
            'rename' => [
                $output->select('Technology to rename (? to list)', $this->technologies->slugs()),
                $output->ask('New name', null, $this->required(...)),
            ],
            'delete' => [$output->select('Technology to delete (? to list)', $this->technologies->slugs())],
            'merge' => [
                $output->select('Technology to merge and delete (? to list)', $this->technologies->slugs()),
                $output->select('Technology that receives the projects (? to list)', $this->technologies->slugs()),
            ],
            default => [],
        };

        if ($values !== []) {
            $input->setArgument('values', $values);
        }
    }

    protected function do(InputInterface $input, OutputInterface $output): int
    {
        $action = (string) $input->getArgument('action');
        $values = array_values((array) $input->getArgument('values'));
        $force = (bool) $input->getOption('force');

        if (!in_array($action, self::ACTIONS, true)) {
            throw new InvalidInputException(sprintf('Unknown action "%s": use %s.', $action, implode(', ', self::ACTIONS)));
        }

        try {
            return match ($action) {
                'list' => $this->list($output),
                'add' => $this->add($output, $this->value($values, 0, 'name'), $input->getOption('slug')),
                'rename' => $this->rename($output, $this->value($values, 0, 'slug'), $this->value($values, 1, 'name'), $input->getOption('slug')),
                'delete' => $this->delete($output, $this->value($values, 0, 'slug'), $force),
                'merge' => $this->merge($output, $this->value($values, 0, 'source'), $this->value($values, 1, 'target'), $force),
            };
        } catch (AdminException $exception) {
            $output->error($exception->getMessage());

            return self::FAILURE;
        }
    }

    private function list(OutputInterface $output): int
    {
        $rows = array_map(static fn (array $row): array => [$row['id'], $row['name'], $row['slug'], $row['projects']], $this->technologies->all());

        if ($rows === []) {
            $output->note('No technology yet.');

            return self::SUCCESS;
        }

        $output->table(['ID', 'Name', 'Slug', 'Projects'], $rows);

        return self::SUCCESS;
    }

    private function add(OutputInterface $output, string $name, ?string $slug): int
    {
        $technology = $this->technologies->add($name, $slug);
        $output->success(sprintf('Technology "%s" added (slug: %s).', $technology->getName(), $technology->getSlug()));

        return self::SUCCESS;
    }

    private function rename(OutputInterface $output, string $slug, string $name, ?string $newSlug): int
    {
        $technology = $this->technologies->rename($slug, $name, $newSlug);
        $output->success(sprintf('Technology renamed to "%s" (slug: %s).', $technology->getName(), $technology->getSlug()));

        return self::SUCCESS;
    }

    private function delete(OutputInterface $output, string $slug, bool $force): int
    {
        if (!$force && !$output->confirm(sprintf('Delete the technology "%s"?', $slug), false)) {
            return self::SUCCESS;
        }

        $detached = $this->technologies->delete($slug, $force);
        $output->success(sprintf('Technology "%s" deleted (%d project(s) detached).', $slug, $detached));

        return self::SUCCESS;
    }

    private function merge(OutputInterface $output, string $source, string $target, bool $force): int
    {
        if (!$force && !$output->confirm(sprintf('Move the projects of "%s" to "%s" and delete "%s"?', $source, $target, $source), false)) {
            return self::SUCCESS;
        }

        $moved = $this->technologies->merge($source, $target);
        $output->success(sprintf('"%s" merged into "%s" (%d project(s) moved).', $source, $target, $moved));

        return self::SUCCESS;
    }

    private function value(array $values, int $index, string $name): string
    {
        $value = trim((string) ($values[$index] ?? ''));

        if ($value === '') {
            throw new InvalidInputException(sprintf('The value "%s" is missing.', $name));
        }

        return $value;
    }

    private function required(?string $value): string
    {
        $value = trim((string) $value);

        return $value !== '' ? $value : throw new \RuntimeException('This value is required.');
    }
}