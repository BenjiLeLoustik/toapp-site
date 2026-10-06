<?php

declare(strict_types=1);

namespace App\Command\Admin;

use App\Admin\Exception\AdminException;
use App\Admin\Helper\CategoryAdminHelper;
use NeoPHP\Process\Console\Attribute\AsCommand;
use NeoPHP\Process\Console\Contract\AbstractConsole;
use NeoPHP\Process\Console\Contract\InputInterface;
use NeoPHP\Process\Console\Contract\OutputInterface;
use NeoPHP\Process\Console\Exception\InvalidInputException;
use NeoPHP\Process\Console\IO\InputArgument;
use NeoPHP\Process\Console\IO\InputOption;

#[AsCommand(name: 'admin:category', description: 'Manages the categories', aliases: ['category'])]
class CategoryCommand extends AbstractConsole
{
    public const ACTIONS = ['list', 'add', 'edit', 'translate', 'delete'];

    public function __construct(
        private CategoryAdminHelper $categories,
    ) {
    }

    protected function configure(InputInterface $input, OutputInterface $output): void
    {
        $input->addArgument('action', InputArgument::REQUIRED, 'list, add, edit, translate or delete');
        $input->addArgument('slug', InputArgument::OPTIONAL, 'Slug of the category');
        $input->addOption('icon', null, InputOption::VALUE_REQUIRED, 'Lucide icon (add, edit)');
        $input->addOption('name', null, InputOption::VALUE_REQUIRED, 'Name (add, translate)');
        $input->addOption('description', null, InputOption::VALUE_REQUIRED, 'Description (add, translate)');
        $input->addOption('locale', null, InputOption::VALUE_REQUIRED, 'Locale of the translation', 'en');
        $input->addOption('new-slug', null, InputOption::VALUE_REQUIRED, 'New slug (edit)');
        $input->addOption('move-to', null, InputOption::VALUE_REQUIRED, 'Category that receives the projects (delete)');

        $this->setHelp(implode("\n", [
            'list [--locale=en]                                       lists the categories',
            'add <slug> --icon= --name= --description= [--locale=]    adds a category with its first translation',
            'edit <slug> [--icon=] [--new-slug=]                      changes the icon or the slug',
            'translate <slug> --locale= --name= --description=        adds or updates a translation',
            'delete <slug> [--move-to=<slug>]                         deletes a category (its projects are moved)',
        ]));

        $this->addExample('app:category list');
        $this->addExample('app:category add web-apps --icon=globe --name="Web apps" --description="Applications running in the browser"');
        $this->addExample('app:category translate web-apps --locale=fr --name="Applications web" --description="Applications dans le navigateur"');
        $this->addExample('app:category delete old-category --move-to=web-apps');
    }

    protected function interact(InputInterface $input, OutputInterface $output): void
    {
        if (!$input->isArgumentProvided('action')) {
            $input->setArgument('action', $output->choice('Action', self::ACTIONS, 'list'));
        }

        $action = (string) $input->getArgument('action');

        if ($action === 'list') {
            return;
        }

        if (!$input->isArgumentProvided('slug')) {
            $input->setArgument('slug', $action === 'add'
                ? $output->ask('Slug of the new category (e.g. web-apps)', null, $this->required(...))
                : $output->select('Category (? to list)', $this->categories->slugs()));
        }

        if (in_array($action, ['add', 'edit'], true) && !$input->isOptionProvided('icon')) {
            $icon = $output->ask('Lucide icon (e.g. globe, smartphone)' . ($action === 'edit' ? ', empty to keep it' : ''), null, $action === 'add' ? $this->required(...) : null);
            $input->setOption('icon', $icon !== '' ? $icon : null);
        }

        if (in_array($action, ['add', 'translate'], true)) {
            if ($action === 'translate' && !$input->isOptionProvided('locale')) {
                $input->setOption('locale', $output->ask('Locale (e.g. en, fr)', 'en', $this->required(...)));
            }

            if (!$input->isOptionProvided('name')) {
                $input->setOption('name', $output->ask('Name', null, $this->required(...)));
            }

            if (!$input->isOptionProvided('description')) {
                $input->setOption('description', $output->ask('Description', ''));
            }
        }

        if ($action === 'delete' && !$input->isOptionProvided('move-to')) {
            $moveTo = $output->select('Category that receives the projects (empty if it has none)', $this->categories->slugs(), null, false);
            $input->setOption('move-to', $moveTo);
        }
    }

    protected function do(InputInterface $input, OutputInterface $output): int
    {
        $action = (string) $input->getArgument('action');
        $slug = trim((string) $input->getArgument('slug'));
        $locale = (string) $input->getOption('locale');

        if (!in_array($action, self::ACTIONS, true)) {
            throw new InvalidInputException(sprintf('Unknown action "%s": use %s.', $action, implode(', ', self::ACTIONS)));
        }

        if ($action !== 'list' && $slug === '') {
            throw new InvalidInputException('The slug of the category is missing.');
        }

        try {
            switch ($action) {
                case 'list':
                    $rows = array_map(
                        static fn (array $row): array => [$row['id'], $row['slug'], $row['icon'], $row['name'], $row['locales'], $row['projects']],
                        $this->categories->all($locale)
                    );

                    $rows === []
                        ? $output->note('No category yet.')
                        : $output->table(['ID', 'Slug', 'Icon', 'Name (' . $locale . ')', 'Locales', 'Projects'], $rows);
                    break;

                case 'add':
                    $category = $this->categories->add(
                        $slug,
                        (string) $input->getOption('icon'),
                        $locale,
                        (string) $input->getOption('name'),
                        (string) $input->getOption('description')
                    );
                    $output->success(sprintf('Category "%s" added.', $category->getSlug()));
                    break;

                case 'edit':
                    $category = $this->categories->edit($slug, $input->getOption('icon'), $input->getOption('new-slug'));
                    $output->success(sprintf('Category "%s" updated.', $category->getSlug()));
                    break;

                case 'translate':
                    $this->categories->translate(
                        $slug,
                        $locale,
                        (string) $input->getOption('name'),
                        (string) $input->getOption('description')
                    );
                    $output->success(sprintf('Translation "%s" of the category "%s" saved.', $locale, $slug));
                    break;

                case 'delete':
                    if (!$input->getOption('force') && !$output->confirm(sprintf('Delete the category "%s"?', $slug), false)) {
                        return self::SUCCESS;
                    }

                    $moved = $this->categories->delete($slug, $input->getOption('move-to'));
                    $output->success(sprintf('Category "%s" deleted (%d project(s) moved).', $slug, $moved));
                    break;
            }
        } catch (AdminException $exception) {
            $output->error($exception->getMessage());

            return self::FAILURE;
        }

        return self::SUCCESS;
    }

    private function required(?string $value): string
    {
        $value = trim((string) $value);

        return $value !== '' ? $value : throw new \RuntimeException('This value is required.');
    }
}