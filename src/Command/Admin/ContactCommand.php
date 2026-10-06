<?php

declare(strict_types=1);

namespace App\Command\Admin;

use App\Admin\Exception\AdminException;
use App\Admin\Helper\ContactAdminHelper;
use NeoPHP\Process\Console\Attribute\AsCommand;
use NeoPHP\Process\Console\Contract\AbstractConsole;
use NeoPHP\Process\Console\Contract\InputInterface;
use NeoPHP\Process\Console\Contract\OutputInterface;
use NeoPHP\Process\Console\Exception\InvalidInputException;
use NeoPHP\Process\Console\IO\InputArgument;
use NeoPHP\Process\Console\IO\InputOption;

#[AsCommand(name: 'app:contact', description: 'Manages the contact page: details, subjects and messages', aliases: ['contact'])]
class ContactCommand extends AbstractConsole
{
    public const ACTIONS = [
        'messages', 'message-show', 'message-status',
        'configs', 'config-add', 'config-edit', 'config-enable', 'config-disable', 'config-delete',
        'objects', 'object-add', 'object-translate', 'object-delete',
        'types', 'type-add', 'type-translate', 'type-delete',
    ];

    public const DESTRUCTIVE = ['config-delete', 'object-delete', 'type-delete'];

    public function __construct(
        private ContactAdminHelper $contact,
    ) {
    }

    protected function configure(InputInterface $input, OutputInterface $output): void
    {
        $input->addArgument('action', InputArgument::REQUIRED, 'Action (see the help)');
        $input->addArgument('values', InputArgument::OPTIONAL | InputArgument::IS_ARRAY, 'Values of the action (id, slug, value...)');
        $input->addOption('locale', null, InputOption::VALUE_REQUIRED, 'Locale of the names', 'en');
        $input->addOption('name', null, InputOption::VALUE_REQUIRED, 'Name (type-add, type-translate, object-add, object-translate)');
        $input->addOption('icon', null, InputOption::VALUE_REQUIRED, 'Lucide icon (type-add)');
        $input->addOption('format', null, InputOption::VALUE_REQUIRED, 'Format (type-add)');
        $input->addOption('status', 's', InputOption::VALUE_REQUIRED, 'Status filter (messages)');
        $input->addOption('limit', 'l', InputOption::VALUE_REQUIRED, 'Maximum number of results (messages)', '20');

        $this->setHelp(implode("\n", [
            'Messages',
            '  messages [--status=] [--limit=]          lists the received messages, the most recent first',
            '  message-show <id>                        displays a message',
            '  message-status <id> <status>             changes the status of a message',
            'Contact details',
            '  configs                                  lists the contact details',
            '  config-add <type> <value>                adds a contact detail (e.g. email contact@toapp.dev)',
            '  config-edit <id> <value>                 changes the value',
            '  config-enable / config-disable <id>      shows or hides it on the contact page',
            '  config-delete <id>                       deletes it',
            'Subjects of the form',
            '  objects                                  lists the subjects',
            '  object-add <slug> --name=                adds a subject',
            '  object-translate <slug> --locale= --name= translates a subject',
            '  object-delete <slug>                     deletes an unused subject',
            'Types of contact details',
            '  types                                    lists the types (email, phone, address...)',
            '  type-add <slug> --icon= --format= --name= adds a type',
            '  type-translate <slug> --locale= --name=  translates a type',
            '  type-delete <slug>                       deletes a type without contact details',
        ]));

        $this->addExample('app:contact messages --status=new');
        $this->addExample('app:contact message-show 12');
        $this->addExample('app:contact config-add email contact@toapp.dev');
        $this->addExample('app:contact object-add partnership --name="Partnership"');
    }

    protected function interact(InputInterface $input, OutputInterface $output): void
    {
        if (!$input->isArgumentProvided('action')) {
            $input->setArgument('action', $output->choice('Action', self::ACTIONS, 'messages'));
        }

        if ($input->isArgumentProvided('values')) {
            return;
        }

        $action = (string) $input->getArgument('action');
        $id = fn (string $label): string => $output->ask($label, null, $this->number(...));

        $values = match ($action) {
            'message-show' => [$id('Id of the message')],
            'message-status' => [$id('Id of the message'), $output->choice('New status', $this->contact->statuses())],
            'config-add' => [
                $output->select('Type (? to list)', $this->contact->typeSlugs()),
                $output->ask('Value', null, $this->required(...)),
            ],
            'config-edit' => [$id('Id of the contact detail'), $output->ask('New value', null, $this->required(...))],
            'config-enable', 'config-disable', 'config-delete' => [$id('Id of the contact detail')],
            'object-add', 'type-add' => [$output->ask('Slug', null, $this->required(...))],
            'object-translate', 'object-delete' => [$output->select('Subject (? to list)', $this->contact->objectSlugs())],
            'type-translate', 'type-delete' => [$output->select('Type (? to list)', $this->contact->typeSlugs())],
            default => [],
        };

        if ($values !== []) {
            $input->setArgument('values', $values);
        }

        if (in_array($action, ['object-translate', 'type-translate'], true) && !$input->isOptionProvided('locale')) {
            $input->setOption('locale', $output->ask('Locale (e.g. en, fr)', 'en', $this->required(...)));
        }

        if (in_array($action, ['object-add', 'object-translate', 'type-add', 'type-translate'], true) && !$input->isOptionProvided('name')) {
            $input->setOption('name', $output->ask('Name', null, $this->required(...)));
        }

        if ($action === 'type-add') {
            if (!$input->isOptionProvided('icon')) {
                $input->setOption('icon', $output->ask('Lucide icon (e.g. mail, phone, map-pin)', null, $this->required(...)));
            }

            if (!$input->isOptionProvided('format')) {
                $input->setOption('format', $output->choice('Format', $this->contact->formats()));
            }
        }
    }

    protected function do(InputInterface $input, OutputInterface $output): int
    {
        $action = (string) $input->getArgument('action');
        $values = array_values((array) $input->getArgument('values'));
        $locale = (string) $input->getOption('locale');
        $name = (string) $input->getOption('name');

        if (!in_array($action, self::ACTIONS, true)) {
            throw new InvalidInputException(sprintf('Unknown action "%s": run "app:contact --help" to see them.', $action));
        }

        try {
            if (in_array($action, self::DESTRUCTIVE, true)
                && !$input->getOption('force')
                && !$output->confirm(sprintf('Run "%s" on "%s"?', $action, $this->value($values, 0, 'id or slug')), false)) {
                return self::SUCCESS;
            }

            switch ($action) {
                case 'messages':
                    $rows = $this->contact->messages($input->getOption('status'), max(1, (int) $input->getOption('limit')));
                    $rows === []
                        ? $output->note('No message.')
                        : $output->table(['ID', 'Date', 'Name', 'Email', 'Subject', 'Status'], $rows);
                    break;

                case 'message-show':
                    $message = $this->contact->message($this->id($values));
                    $output->definitionList($this->contact->messageDetails($message));
                    $output->section('Message');
                    $output->text($message->getMessage());
                    break;

                case 'message-status':
                    $message = $this->contact->setMessageStatus($this->id($values), $this->value($values, 1, 'status'));
                    $output->success(sprintf('Message #%d is now "%s".', $message->getId(), $message->getStatus()->value));
                    break;

                case 'configs':
                    $rows = $this->contact->configs($locale);
                    $rows === []
                        ? $output->note('No contact detail yet.')
                        : $output->table(['ID', 'Type', 'Name (' . $locale . ')', 'Value', 'Enabled'], $rows);
                    break;

                case 'config-add':
                    $config = $this->contact->addConfig($this->value($values, 0, 'type'), $this->value($values, 1, 'value'));
                    $output->success(sprintf('Contact detail #%d added.', $config->getId()));
                    break;

                case 'config-edit':
                    $this->contact->editConfig($this->id($values), $this->value($values, 1, 'value'));
                    $output->success('Contact detail updated.');
                    break;

                case 'config-enable':
                case 'config-disable':
                    $this->contact->enableConfig($this->id($values), $action === 'config-enable');
                    $output->success(sprintf('Contact detail %s.', $action === 'config-enable' ? 'enabled' : 'disabled'));
                    break;

                case 'config-delete':
                    $this->contact->deleteConfig($this->id($values));
                    $output->success('Contact detail deleted.');
                    break;

                case 'objects':
                    $rows = $this->contact->objects($locale);
                    $rows === []
                        ? $output->note('No subject yet.')
                        : $output->table(['ID', 'Slug', 'Name (' . $locale . ')', 'Locales', 'Messages'], $rows);
                    break;

                case 'object-add':
                    $object = $this->contact->addObject($this->value($values, 0, 'slug'), $locale, $name);
                    $output->success(sprintf('Subject "%s" added.', $object->getSlug()));
                    break;

                case 'object-translate':
                    $this->contact->translateObjectBySlug($this->value($values, 0, 'slug'), $locale, $name);
                    $output->success(sprintf('Translation "%s" saved.', $locale));
                    break;

                case 'object-delete':
                    $this->contact->deleteObject($this->value($values, 0, 'slug'));
                    $output->success('Subject deleted.');
                    break;

                case 'types':
                    $rows = $this->contact->types($locale);
                    $rows === []
                        ? $output->note('No contact type yet.')
                        : $output->table(['ID', 'Slug', 'Icon', 'Format', 'Name (' . $locale . ')', 'Locales', 'Details'], $rows);
                    break;

                case 'type-add':
                    $type = $this->contact->addType(
                        $this->value($values, 0, 'slug'),
                        (string) $input->getOption('icon'),
                        (string) $input->getOption('format'),
                        $locale,
                        $name
                    );
                    $output->success(sprintf('Contact type "%s" added.', $type->getSlug()));
                    break;

                case 'type-translate':
                    $this->contact->translateTypeBySlug($this->value($values, 0, 'slug'), $locale, $name);
                    $output->success(sprintf('Translation "%s" saved.', $locale));
                    break;

                case 'type-delete':
                    $this->contact->deleteType($this->value($values, 0, 'slug'));
                    $output->success('Contact type deleted.');
                    break;
            }
        } catch (AdminException $exception) {
            $output->error($exception->getMessage());

            return self::FAILURE;
        }

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

    private function id(array $values): int
    {
        $value = $this->value($values, 0, 'id');

        if (!ctype_digit($value)) {
            throw new InvalidInputException(sprintf('"%s" is not a valid id.', $value));
        }

        return (int) $value;
    }

    private function number(?string $value): string
    {
        return ctype_digit(trim((string) $value)) ? trim((string) $value) : throw new \RuntimeException('The id must be a number.');
    }

    private function required(?string $value): string
    {
        $value = trim((string) $value);

        return $value !== '' ? $value : throw new \RuntimeException('This value is required.');
    }
}