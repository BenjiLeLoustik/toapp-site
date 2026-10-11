<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Admin\Contract\AbstractToolController;
use App\Admin\Helper\ContactAdminHelper;
use App\Entity\WebsiteContactConfig;
use App\Entity\WebsiteContactMessage;
use App\Entity\WebsiteContactObject;
use App\Entity\WebsiteContactType;
use NeoPHP\Component\Form\Type\ChoiceType;
use NeoPHP\Component\Form\Type\TextType;
use NeoPHP\Component\Http\Response\Response;
use NeoPHP\Component\Routing\Attribute\Route;
use NeoPHP\NeoAdmin\Page\Action;
use NeoPHP\NeoAdmin\Page\Block\Card;
use NeoPHP\NeoAdmin\Page\Column;
use NeoPHP\NeoAdmin\Page\Page;

class ContactController extends AbstractToolController
{
    public const TABS = ['messages' => 'Messages', 'configs' => 'Contact details', 'objects' => 'Subjects', 'types' => 'Types'];

    public const COLUMNS = [
        'messages' => ['id', 'date', 'name', 'email', 'subject', 'status'],
        'configs' => ['id', 'type', 'name', 'value', 'enabled'],
        'objects' => ['id', 'slug', 'name', 'locales', 'messages'],
        'types' => ['id', 'slug', 'icon', 'format', 'name', 'locales', 'configs'],
    ];

    public const LIMIT = 50;

    #[Route('/admin/contact', name: 'admin_contact', methods: ['GET', 'POST'])]
    public function index(): Response
    {
        $this->denyUnlessAdmin();
        $tab = $this->request()->query->getString('tab', 'messages');
        $tab = isset(self::TABS[$tab]) ? $tab : 'messages';
        $page = $this->page('Contact')
            ->description('The messages, the contact details and the subjects of the contact page.')
            ->tabs('tab', self::TABS, $tab);

        return match ($tab) {
            'messages' => $this->messages($page),
            'configs' => $this->configs($page),
            'objects' => $this->objects($page),
            'types' => $this->types($page),
        };
    }

    #[Route('/admin/contact/messages/{id}', name: 'admin_contact_message', methods: ['GET', 'POST'], requirements: ['id' => '\d+'])]
    public function message(string $id): Response
    {
        $this->denyUnlessAdmin();
        $contact = $this->get(ContactAdminHelper::class);
        $message = $this->findOr404(static fn (): WebsiteContactMessage => $contact->message((int) $id));
        $statuses = $contact->statuses();
        $form = $this->createAdminForm('contact_message_status', ['status' => $message->getStatus()->value], [
            'status' => [ChoiceType::class, ['label' => 'Status', 'choices' => array_combine($statuses, $statuses)]],
        ]);

        if (($data = $this->submitted($form)) !== null && $this->attempt(
                static fn (): WebsiteContactMessage => $contact->setMessageStatus((int) $id, (string) $data['status']),
                static fn (WebsiteContactMessage $message): string => sprintf('Message #%d is now "%s".', $message->getId(), $message->getStatus()->value),
            )) {
            return $this->redirectToRoute('admin_contact_message', ['id' => $id]);
        }

        return $this->page(sprintf('Message #%d', $message->getId()))
            ->description(sprintf('From %s.', $message->getEmail()))
            ->action(Action::link('Back', $this->generateUrl('admin_contact'), 'chevron-right'))
            ->action(Action::link('Reply', 'mailto:' . $message->getEmail(), 'mail')->primary())
            ->add($this->details($contact->messageDetails($message), 'Details', 'mail'))
            ->card('Message', static fn (Card $card): Card => $card->text((string) $message->getMessage()), 'mail')
            ->form($form, 'Change the status', null, 'Status')
            ->render($this->status($form));
    }

    #[Route('/admin/contact/configs/{id}', name: 'admin_contact_config', methods: ['GET', 'POST'], requirements: ['id' => '\d+'])]
    public function config(string $id): Response
    {
        $this->denyUnlessAdmin();
        $contact = $this->get(ContactAdminHelper::class);
        $config = $this->findOr404(static fn (): WebsiteContactConfig => $contact->config((int) $id));
        $form = $this->createAdminForm('contact_config_edit', ['value' => $config->getValue()], [
            'value' => [TextType::class, ['label' => 'Value']],
        ]);

        if (($data = $this->submitted($form)) !== null && $this->attempt(
                static fn (): WebsiteContactConfig => $contact->editConfig((int) $id, (string) $data['value']),
                'Contact detail updated.',
            )) {
            return $this->redirectToRoute('admin_contact', ['tab' => 'configs']);
        }

        return $this->page(sprintf('Contact detail #%d', $config->getId()))
            ->description(sprintf('Type: %s.', $config->getType()?->getSlug() ?? '-'))
            ->form($form, 'Save', $this->generateUrl('admin_contact', ['tab' => 'configs']))
            ->render($this->status($form));
    }

    #[Route('/admin/contact/{kind}/{slug}', name: 'admin_contact_translate', methods: ['GET', 'POST'], requirements: ['kind' => 'objects|types'])]
    public function translation(string $kind, string $slug): Response
    {
        $this->denyUnlessAdmin();
        $contact = $this->get(ContactAdminHelper::class);
        $entity = $this->findOr404(static fn (): WebsiteContactObject|WebsiteContactType => $kind === 'objects' ? $contact->object($slug) : $contact->type($slug));
        $locale = $this->locale();
        $name = '';

        foreach ($entity->getTranslations() as $translation) {
            if ($translation->getLocale() === $locale) {
                $name = (string) $translation->getName();
            }
        }

        $form = $this->createAdminForm('contact_translate', ['name' => $name], [
            'name' => [TextType::class, ['label' => 'Name']],
        ]);

        if (($data = $this->submitted($form)) !== null && $this->attempt(
                static fn (): mixed => $kind === 'objects'
                    ? $contact->translateObjectBySlug($slug, $locale, (string) $data['name'])
                    : $contact->translateTypeBySlug($slug, $locale, (string) $data['name']),
                sprintf('Translation "%s" saved.', $locale),
            )) {
            return $this->redirectToRoute('admin_contact', ['tab' => $kind]);
        }

        return $this->page(sprintf('%s "%s"', $kind === 'objects' ? 'Subject' : 'Type', $slug))
            ->action(Action::link('Back', $this->generateUrl('admin_contact', ['tab' => $kind]), 'chevron-right'))
            ->tabs('locale', $this->localeTabs(), $locale)
            ->form($form, 'Save the translation', null, sprintf('Translation (%s)%s', $locale, $name === '' ? ' · missing' : ''))
            ->render($this->status($form));
    }

    #[Route('/admin/contact/{kind}/{key}/{action}', name: 'admin_contact_action', methods: ['POST'], requirements: ['kind' => 'configs|objects|types', 'action' => 'enable|disable|delete'])]
    public function action(string $kind, string $key, string $action): Response
    {
        $this->denyUnlessAdmin();
        $contact = $this->get(ContactAdminHelper::class);

        $this->handlePost(
            'contact_' . $kind . '_' . $key,
            static fn (): mixed => match ($kind . '-' . $action) {
                'configs-enable' => $contact->enableConfig((int) $key, true),
                'configs-disable' => $contact->enableConfig((int) $key, false),
                'configs-delete' => $contact->deleteConfig((int) $key),
                'objects-delete' => $contact->deleteObject($key),
                'types-delete' => $contact->deleteType($key),
            },
            match ($action) {
                'enable' => 'Contact detail enabled.',
                'disable' => 'Contact detail disabled.',
                default => 'Deleted.',
            },
        );

        return $this->redirectToRoute('admin_contact', ['tab' => $kind]);
    }

    protected function messages(Page $page): Response
    {
        $contact = $this->get(ContactAdminHelper::class);
        $status = $this->request()->query->getString('status');
        $status = in_array($status, $contact->statuses(), true) ? $status : null;
        $show = fn (array $row): string => $this->generateUrl('admin_contact_message', ['id' => $row['id']]);
        $statuses = ['' => 'All'];

        foreach ($contact->statuses() as $value) {
            $statuses[$value] = ucfirst($value);
        }

        return $page
            ->tabs('status', $statuses, $status ?? '')
            ->table(null, $this->rows('messages', $contact->messages($status, self::LIMIT)), [
                Column::text('date', 'Date')->url($show),
                Column::text('name', 'Name'),
                Column::text('email', 'Email'),
                Column::code('subject', 'Subject'),
                Column::badge('status', 'Status'),
            ], 'mail', 'No message.', static fn (array $row): array => [
                Action::link('Show', $show($row), 'eye'),
            ])
            ->render();
    }

    protected function configs(Page $page): Response
    {
        $contact = $this->get(ContactAdminHelper::class);
        $types = $contact->typeSlugs();
        $form = $this->createAdminForm('contact_config_add', ['type' => null, 'value' => ''], [
            'type' => [ChoiceType::class, ['label' => 'Type', 'choices' => array_combine($types, $types)]],
            'value' => [TextType::class, ['label' => 'Value', 'help' => 'e.g. contact@toapp.dev']],
        ]);

        if (($data = $this->submitted($form)) !== null && $this->attempt(
                static fn (): WebsiteContactConfig => $contact->addConfig((string) $data['type'], (string) $data['value']),
                static fn (WebsiteContactConfig $config): string => sprintf('Contact detail #%d added.', $config->getId()),
            )) {
            return $this->redirectToRoute('admin_contact', ['tab' => 'configs']);
        }

        $locale = $this->locale();

        return $this
            ->list($page->tabs('locale', $this->localeTabs(), $locale), 'configs', $contact->configs($locale), [
                Column::text('value', 'Value')->url(fn (array $row): string => $this->generateUrl('admin_contact_config', ['id' => $row['id']])),
                Column::code('type', 'Type'),
                Column::text('name', 'Name (' . $locale . ')'),
                Column::badge('enabled', 'Enabled', ['yes' => 'success', 'no' => 'warning']),
            ], 'id', fn (array $row): array => [
                Action::link('Edit', $this->generateUrl('admin_contact_config', ['id' => $row['id']]), 'edit'),
                $row['enabled'] === 'yes'
                    ? Action::post('Disable', $this->generateUrl('admin_contact_action', ['kind' => 'configs', 'key' => $row['id'], 'action' => 'disable']), 'contact_configs_' . $row['id'], null, 'eye')
                    : Action::post('Enable', $this->generateUrl('admin_contact_action', ['kind' => 'configs', 'key' => $row['id'], 'action' => 'enable']), 'contact_configs_' . $row['id'], null, 'eye'),
            ])
            ->form($form, 'Add', null, 'New contact detail')
            ->render($this->status($form));
    }

    protected function objects(Page $page): Response
    {
        $contact = $this->get(ContactAdminHelper::class);
        $form = $this->createAdminForm('contact_object_add', ['slug' => '', 'locale' => $this->defaultLocale(), 'name' => ''], [
            'slug' => [TextType::class, ['label' => 'Slug', 'help' => 'e.g. partnership']],
            'locale' => [ChoiceType::class, ['label' => 'Locale', 'choices' => $this->localeChoices()]],
            'name' => [TextType::class, ['label' => 'Name']],
        ]);

        if (($data = $this->submitted($form)) !== null && $this->attempt(
                static fn (): WebsiteContactObject => $contact->addObject((string) $data['slug'], (string) $data['locale'], (string) $data['name']),
                static fn (WebsiteContactObject $object): string => sprintf('Subject "%s" added.', $object->getSlug()),
            )) {
            return $this->redirectToRoute('admin_contact', ['tab' => 'objects']);
        }

        $locale = $this->locale();

        return $this
            ->list($page->tabs('locale', $this->localeTabs(), $locale), 'objects', $contact->objects($locale), [
                Column::text('name', 'Name (' . $locale . ')')->url(fn (array $row): string => $this->generateUrl('admin_contact_translate', ['kind' => 'objects', 'slug' => $row['slug'], 'locale' => $locale])),
                Column::code('slug', 'Slug'),
                Column::text('locales', 'Locales'),
                Column::number('messages', 'Messages'),
            ], 'slug')
            ->form($form, 'Add', null, 'New subject')
            ->render($this->status($form));
    }

    protected function types(Page $page): Response
    {
        $contact = $this->get(ContactAdminHelper::class);
        $formats = $contact->formats();
        $form = $this->createAdminForm('contact_type_add', ['slug' => '', 'icon' => '', 'format' => null, 'locale' => $this->defaultLocale(), 'name' => ''], [
            'slug' => [TextType::class, ['label' => 'Slug', 'help' => 'e.g. email']],
            'icon' => [TextType::class, ['label' => 'Lucide icon', 'help' => 'e.g. mail, phone, map-pin']],
            'format' => [ChoiceType::class, ['label' => 'Format', 'choices' => array_combine($formats, $formats)]],
            'locale' => [ChoiceType::class, ['label' => 'Locale', 'choices' => $this->localeChoices()]],
            'name' => [TextType::class, ['label' => 'Name']],
        ]);

        if (($data = $this->submitted($form)) !== null && $this->attempt(
                static fn (): WebsiteContactType => $contact->addType((string) $data['slug'], (string) $data['icon'], (string) $data['format'], (string) $data['locale'], (string) $data['name']),
                static fn (WebsiteContactType $type): string => sprintf('Contact type "%s" added.', $type->getSlug()),
            )) {
            return $this->redirectToRoute('admin_contact', ['tab' => 'types']);
        }

        $locale = $this->locale();

        return $this
            ->list($page->tabs('locale', $this->localeTabs(), $locale), 'types', $contact->types($locale), [
                Column::text('name', 'Name (' . $locale . ')')->url(fn (array $row): string => $this->generateUrl('admin_contact_translate', ['kind' => 'types', 'slug' => $row['slug'], 'locale' => $locale])),
                Column::code('slug', 'Slug'),
                Column::code('icon', 'Icon'),
                Column::badge('format', 'Format'),
                Column::text('locales', 'Locales'),
                Column::number('configs', 'Details'),
            ], 'slug')
            ->form($form, 'Add', null, 'New type')
            ->render($this->status($form));
    }

    protected function list(Page $page, string $kind, array $rows, array $columns, string $key, ?callable $actions = null): Page
    {
        return $page->table(null, $this->rows($kind, $rows), $columns, null, 'Nothing here yet.', fn (array $row): array => [
            ...($actions !== null ? $actions($row) : [Action::link('Translate', $this->generateUrl('admin_contact_translate', ['kind' => $kind, 'slug' => $row['slug']]), 'edit')]),
            Action::post(
                'Delete',
                $this->generateUrl('admin_contact_action', ['kind' => $kind, 'key' => $row[$key], 'action' => 'delete']),
                'contact_' . $kind . '_' . $row[$key],
                'Delete it?',
                'trash',
            )->danger(),
        ]);
    }

    protected function rows(string $kind, array $rows): array
    {
        return array_map(static fn (array $row): array => array_combine(self::COLUMNS[$kind], $row), $rows);
    }
}