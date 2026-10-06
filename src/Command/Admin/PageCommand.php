<?php

declare(strict_types=1);

namespace App\Command\Admin;

use App\Admin\Exception\AdminException;
use App\Admin\Helper\PageAdminHelper;
use NeoPHP\Process\Console\Attribute\AsCommand;
use NeoPHP\Process\Console\Contract\AbstractConsole;
use NeoPHP\Process\Console\Contract\InputInterface;
use NeoPHP\Process\Console\Contract\OutputInterface;
use NeoPHP\Process\Console\Exception\InvalidInputException;
use NeoPHP\Process\Console\IO\InputArgument;
use NeoPHP\Process\Console\IO\InputOption;

#[AsCommand(name: 'app:page', description: 'Manages the legal pages', aliases: ['page'])]
class PageCommand extends AbstractConsole
{
    public const ACTIONS = ['list', 'add', 'translate', 'publish', 'unpublish', 'delete'];

    public function __construct(
        private PageAdminHelper $pages,
    ) {
    }

    protected function configure(InputInterface $input, OutputInterface $output): void
    {
        $input->addArgument('action', InputArgument::REQUIRED, implode(', ', self::ACTIONS));
        $input->addArgument('slug', InputArgument::OPTIONAL, 'Slug of the page (e.g. legal-notice)');
        $input->addOption('locale', null, InputOption::VALUE_REQUIRED, 'Locale of the translation', 'en');
        $input->addOption('title', 't', InputOption::VALUE_REQUIRED, 'Title (add, translate)');
        $input->addOption('file', null, InputOption::VALUE_REQUIRED, 'Markdown file of the content (add, translate)');
        $input->addOption('meta', null, InputOption::VALUE_REQUIRED, 'Meta description (add, translate)');
        $input->addOption('draft', null, InputOption::VALUE_NONE, 'Create the page unpublished (add)');

        $this->setHelp(implode("\n", [
            'list [--locale=en]                                   lists the pages',
            'add <slug> --title= --file= [--meta=] [--draft]      creates a page with its first translation',
            'translate <slug> --locale= [--title=] [--file=]      adds or updates a translation (missing values are kept)',
            'publish / unpublish <slug>                           shows or hides the page',
            'delete <slug>                                        deletes the page and its translations',
        ]));

        $this->addExample('app:page list');
        $this->addExample('app:page add legal-notice --title="Legal notice" --file=docs/legal-notice.en.md');
        $this->addExample('app:page translate legal-notice --locale=fr --title="Mentions légales" --file=docs/legal-notice.fr.md');
        $this->addExample('app:page unpublish terms-of-use');
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
                ? $output->ask('Slug of the new page (e.g. legal-notice)', null, $this->required(...))
                : $output->select('Page (? to list)', $this->pages->slugs()));
        }

        if (!in_array($action, ['add', 'translate'], true)) {
            return;
        }

        if ($action === 'translate' && !$input->isOptionProvided('locale')) {
            $input->setOption('locale', $output->ask('Locale (e.g. en, fr)', 'en', $this->required(...)));
        }

        if (!$input->isOptionProvided('title')) {
            $title = $output->ask('Title' . ($action === 'translate' ? ' (empty to keep it)' : ''), null, $action === 'add' ? $this->required(...) : null);
            $input->setOption('title', $title !== '' ? $title : null);
        }

        if (!$input->isOptionProvided('file')) {
            $file = $output->ask('Markdown file of the content' . ($action === 'translate' ? ' (empty to keep it)' : ''), null, $action === 'add' ? $this->required(...) : null);
            $input->setOption('file', $file !== '' ? $file : null);
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
            throw new InvalidInputException('The slug of the page is missing.');
        }

        try {
            $file = $input->getOption('file');
            $content = $file !== null ? $this->pages->readFile((string) $file) : null;

            switch ($action) {
                case 'list':
                    $rows = $this->pages->all($locale);
                    $rows === []
                        ? $output->note('No page yet.')
                        : $output->table(['ID', 'Slug', 'Title (' . $locale . ')', 'Locales', 'Published', 'Updated'], $rows);
                    break;

                case 'add':
                    $page = $this->pages->add(
                        $slug,
                        $locale,
                        (string) $input->getOption('title'),
                        (string) $content,
                        $input->getOption('meta'),
                        !$input->getOption('draft')
                    );
                    $output->success(sprintf('Page "%s" created%s.', $page->getSlug(), $page->isPublished() ? '' : ' (unpublished)'));
                    break;

                case 'translate':
                    $this->pages->translate($slug, $locale, $input->getOption('title'), $content, $input->getOption('meta'));
                    $output->success(sprintf('Translation "%s" of the page "%s" saved.', $locale, $slug));
                    break;

                case 'publish':
                case 'unpublish':
                    $this->pages->publish($slug, $action === 'publish');
                    $output->success(sprintf('Page "%s" %s.', $slug, $action === 'publish' ? 'published' : 'unpublished'));
                    break;

                case 'delete':
                    if (!$input->getOption('force') && !$output->confirm(sprintf('Delete the page "%s" and all its translations?', $slug), false)) {
                        return self::SUCCESS;
                    }

                    $this->pages->delete($slug);
                    $output->success(sprintf('Page "%s" deleted.', $slug));
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