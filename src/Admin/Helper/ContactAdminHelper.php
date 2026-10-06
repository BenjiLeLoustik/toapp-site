<?php

declare(strict_types=1);

namespace App\Admin\Helper;

use App\Admin\Exception\AdminException;
use App\Entity\WebsiteContactConfig;
use App\Entity\WebsiteContactMessage;
use App\Entity\WebsiteContactObject;
use App\Entity\WebsiteContactObjectTranslated;
use App\Entity\WebsiteContactType;
use App\Entity\WebsiteContactTypeTranslated;
use App\Helper\SlugHelper;
use App\Website\Enum\WebsiteContactFormatEnum;
use App\Website\Enum\WebsiteContactMessageStatusEnum;
use NeoPHP\Package\Orm\Contract\EntityManagerInterface;

class ContactAdminHelper
{
    use AdminValidationTrait;

    public function __construct(
        private EntityManagerInterface $entityManager,
    ) {
    }

    /* Types */

    public function types(string $locale): array
    {
        return array_map(fn (WebsiteContactType $type): array => [
            $type->getId(),
            $type->getSlug(),
            $type->getIcon(),
            $type->getFormat()->value,
            $this->findTranslation($type->getTranslations(), $locale)?->getName() ?? '-',
            $this->locales($type->getTranslations()),
            count($type->getConfigs()),
        ], $this->entityManager->getRepository(WebsiteContactType::class)->findBy([], ['slug' => 'ASC']));
    }

    public function typeSlugs(): array
    {
        return array_map(
            static fn (WebsiteContactType $type): string => $type->getSlug(),
            $this->entityManager->getRepository(WebsiteContactType::class)->findBy([], ['slug' => 'ASC'])
        );
    }

    public function formats(): array
    {
        return array_map(static fn (WebsiteContactFormatEnum $format): string => $format->value, WebsiteContactFormatEnum::cases());
    }

    public function type(string $slug): WebsiteContactType
    {
        $type = $this->entityManager->getRepository(WebsiteContactType::class)->findOneBy(['slug' => $slug]);

        if (!$type instanceof WebsiteContactType) {
            throw new AdminException('The contact type "{slug}" does not exist.', 0, null, ['slug' => $slug]);
        }

        return $type;
    }

    public function addType(string $slug, string $icon, string $format, string $locale, string $name): WebsiteContactType
    {
        $slug = $this->freeSlug(WebsiteContactType::class, $slug, 'contact type');
        $formatEnum = WebsiteContactFormatEnum::tryFrom($format) ?? throw new AdminException(
            'Unknown format "{format}": use {formats}.',
            0,
            null,
            ['format' => $format, 'formats' => implode(', ', $this->formats())]
        );

        $type = (new WebsiteContactType())
            ->setSlug($slug)
            ->setIcon($this->icon($icon))
            ->setFormat($formatEnum);

        $this->entityManager->persist($type);
        $this->translateType($type, $locale, $name);
        $this->entityManager->flush();

        return $type;
    }

    public function translateTypeBySlug(string $slug, string $locale, string $name): void
    {
        $this->translateType($this->type($slug), $locale, $name);
        $this->entityManager->flush();
    }

    public function deleteType(string $slug): void
    {
        $type = $this->type($slug);
        $count = count($type->getConfigs());

        if ($count > 0) {
            throw new AdminException(
                'The contact type "{slug}" still has {count} contact detail(s): delete them first.',
                0,
                null,
                ['slug' => $slug, 'count' => $count]
            );
        }

        $this->entityManager->remove($type);
        $this->entityManager->flush();
    }

    /* Configs */

    public function configs(string $locale): array
    {
        return array_map(fn (WebsiteContactConfig $config): array => [
            $config->getId(),
            $config->getType()?->getSlug() ?? '-',
            $config->getType() !== null ? ($this->findTranslation($config->getType()->getTranslations(), $locale)?->getName() ?? '-') : '-',
            $config->getValue(),
            $config->isEnabled() ? 'yes' : 'no',
        ], $this->entityManager->getRepository(WebsiteContactConfig::class)->findBy([], ['id' => 'ASC']));
    }

    public function config(int $id): WebsiteContactConfig
    {
        $config = $this->entityManager->getRepository(WebsiteContactConfig::class)->find($id);

        if (!$config instanceof WebsiteContactConfig) {
            throw new AdminException('The contact detail #{id} does not exist.', 0, null, ['id' => $id]);
        }

        return $config;
    }

    public function addConfig(string $typeSlug, string $value): WebsiteContactConfig
    {
        $config = (new WebsiteContactConfig())
            ->setValue($this->notEmpty($value, 'value'))
            ->setEnabled(true);

        $this->type($typeSlug)->addConfig($config);
        $this->entityManager->persist($config);
        $this->entityManager->flush();

        return $config;
    }

    public function editConfig(int $id, string $value): WebsiteContactConfig
    {
        $config = $this->config($id)->setValue($this->notEmpty($value, 'value'));
        $this->entityManager->flush();

        return $config;
    }

    public function enableConfig(int $id, bool $enabled): WebsiteContactConfig
    {
        $config = $this->config($id)->setEnabled($enabled);
        $this->entityManager->flush();

        return $config;
    }

    public function deleteConfig(int $id): void
    {
        $config = $this->config($id);
        $config->getType()?->removeConfig($config);
        $this->entityManager->remove($config);
        $this->entityManager->flush();
    }

    /* Objects */

    public function objects(string $locale): array
    {
        return array_map(fn (WebsiteContactObject $object): array => [
            $object->getId(),
            $object->getSlug(),
            $this->findTranslation($object->getTranslations(), $locale)?->getName() ?? '-',
            $this->locales($object->getTranslations()),
            count($object->getMessages()),
        ], $this->entityManager->getRepository(WebsiteContactObject::class)->findAllOrdered());
    }

    public function objectSlugs(): array
    {
        return array_map(
            static fn (WebsiteContactObject $object): string => $object->getSlug(),
            $this->entityManager->getRepository(WebsiteContactObject::class)->findAllOrdered()
        );
    }

    public function object(string $slug): WebsiteContactObject
    {
        $object = $this->entityManager->getRepository(WebsiteContactObject::class)->findOneBy(['slug' => $slug]);

        if (!$object instanceof WebsiteContactObject) {
            throw new AdminException('The contact subject "{slug}" does not exist.', 0, null, ['slug' => $slug]);
        }

        return $object;
    }

    public function addObject(string $slug, string $locale, string $name): WebsiteContactObject
    {
        $object = (new WebsiteContactObject())->setSlug($this->freeSlug(WebsiteContactObject::class, $slug, 'contact subject'));

        $this->entityManager->persist($object);
        $this->translateObject($object, $locale, $name);
        $this->entityManager->flush();

        return $object;
    }

    public function translateObjectBySlug(string $slug, string $locale, string $name): void
    {
        $this->translateObject($this->object($slug), $locale, $name);
        $this->entityManager->flush();
    }

    public function deleteObject(string $slug): void
    {
        $object = $this->object($slug);
        $count = count($object->getMessages());

        if ($count > 0) {
            throw new AdminException(
                'The contact subject "{slug}" is used by {count} message(s) and cannot be deleted.',
                0,
                null,
                ['slug' => $slug, 'count' => $count]
            );
        }

        $this->entityManager->remove($object);
        $this->entityManager->flush();
    }

    /* Messages */

    public function statuses(): array
    {
        return array_map(static fn (WebsiteContactMessageStatusEnum $status): string => $status->value, WebsiteContactMessageStatusEnum::cases());
    }

    public function messages(?string $status, int $limit): array
    {
        $statusEnum = $status !== null && $status !== '' ? $this->status($status) : null;

        return array_map(static fn (WebsiteContactMessage $message): array => [
            $message->getId(),
            $message->getCreatedAt()?->format('Y-m-d H:i'),
            trim($message->getFirstname() . ' ' . $message->getLastname()),
            $message->getEmail(),
            $message->getObject()?->getSlug() ?? '-',
            $message->getStatus()->value,
        ], $this->entityManager->getRepository(WebsiteContactMessage::class)->findLatest($statusEnum, $limit));
    }

    public function message(int $id): WebsiteContactMessage
    {
        $message = $this->entityManager->getRepository(WebsiteContactMessage::class)->find($id);

        if (!$message instanceof WebsiteContactMessage) {
            throw new AdminException('The message #{id} does not exist.', 0, null, ['id' => $id]);
        }

        return $message;
    }

    public function messageDetails(WebsiteContactMessage $message): array
    {
        return [
            'id' => $message->getId(),
            'date' => $message->getCreatedAt()?->format('Y-m-d H:i'),
            'name' => trim($message->getFirstname() . ' ' . $message->getLastname()),
            'email' => $message->getEmail(),
            'subject' => $message->getObject()?->getSlug() ?? '-',
            'website' => $message->getWebsite() ?? '-',
            'consent' => $message->hasConsent() ? 'yes' : 'no',
            'status' => $message->getStatus()->value,
        ];
    }

    public function setMessageStatus(int $id, string $status): WebsiteContactMessage
    {
        $message = $this->message($id)->setStatus($this->status($status));
        $this->entityManager->flush();

        return $message;
    }

    /* Internals */

    private function status(string $status): WebsiteContactMessageStatusEnum
    {
        return WebsiteContactMessageStatusEnum::tryFrom($status) ?? throw new AdminException(
            'Unknown status "{status}": use {statuses}.',
            0,
            null,
            ['status' => $status, 'statuses' => implode(', ', $this->statuses())]
        );
    }

    private function translateType(WebsiteContactType $type, string $locale, string $name): void
    {
        $locale = $this->locale($locale);
        $translation = $this->findTranslation($type->getTranslations(), $locale);

        if (!$translation instanceof WebsiteContactTypeTranslated) {
            $translation = (new WebsiteContactTypeTranslated())->setLocale($locale);
            $type->addTranslation($translation);
            $this->entityManager->persist($translation);
        }

        $translation->setName($this->notEmpty($name, 'name'));
    }

    private function translateObject(WebsiteContactObject $object, string $locale, string $name): void
    {
        $locale = $this->locale($locale);
        $translation = $this->findTranslation($object->getTranslations(), $locale);

        if (!$translation instanceof WebsiteContactObjectTranslated) {
            $translation = (new WebsiteContactObjectTranslated())->setLocale($locale);
            $object->addTranslation($translation);
            $this->entityManager->persist($translation);
        }

        $translation->setName($this->notEmpty($name, 'name'));
    }

    private function freeSlug(string $class, string $slug, string $label): string
    {
        $slug = SlugHelper::slugify($slug, 100);

        if ($slug === '') {
            throw new AdminException('The slug of the {label} cannot be empty.', 0, null, ['label' => $label]);
        }

        if ($this->entityManager->getRepository($class)->findOneBy(['slug' => $slug]) !== null) {
            throw new AdminException('A {label} with the slug "{slug}" already exists.', 0, null, ['label' => $label, 'slug' => $slug]);
        }

        return $slug;
    }
}