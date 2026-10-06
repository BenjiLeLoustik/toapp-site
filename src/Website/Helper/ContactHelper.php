<?php

declare(strict_types=1);

namespace App\Website\Helper;

use App\Entity\WebsiteContactMessage;
use App\Entity\WebsiteContactObject;
use App\Website\Enum\WebsiteContactMessageStatusEnum;
use NeoPHP\Component\Form\Contract\FormInterface;
use NeoPHP\Package\Orm\Contract\EntityManagerInterface;
use NeoPHP\Package\Translation\Contract\TranslatorInterface;

class ContactHelper
{
    public const TRANSLATION_DOMAIN = 'validators';

    public function __construct(
        private EntityManagerInterface $entityManager,
        private TranslatorInterface $translator,
    ) {
    }

    public function validate(FormInterface $form): ?WebsiteContactObject
    {
        /** @var ?WebsiteContactObject $object */
        $object = $this->entityManager->getRepository(WebsiteContactObject::class)->findOneBy([
            'slug' => (string) $form->get('object')->getData(),
        ]);

        if ($object === null && $form->get('object')->getErrors() === []) {
            $form->get('object')->addError($this->translator->translate('contact.object.invalid', [], self::TRANSLATION_DOMAIN));
        }

        return $form->getErrors(true) === [] ? $object : null;
    }

    public function save(FormInterface $form, WebsiteContactObject $object): WebsiteContactMessage
    {
        $website = trim((string) $form->get('website')->getData());

        $message = new WebsiteContactMessage();
        $message->setLastname(trim((string) $form->get('lastname')->getData()));
        $message->setFirstname(trim((string) $form->get('firstname')->getData()));
        $message->setEmail(strtolower(trim((string) $form->get('email')->getData())));
        $message->setObject($object);
        $message->setMessage(trim((string) $form->get('message')->getData()));
        $message->setWebsite($website !== '' ? $website : null);
        $message->setStatus(WebsiteContactMessageStatusEnum::NEW);
        $message->setConsent(true);

        $this->entityManager->persist($message);
        $this->entityManager->flush();

        return $message;
    }

    public function errors(FormInterface $form): array
    {
        $errors = [];

        foreach ($form->all() as $name => $child) {
            $childErrors = $child->getErrors();

            if ($childErrors !== []) {
                $errors[$name] = $childErrors[0]->getMessage();
            }
        }

        $formErrors = $form->getErrors();

        if ($formErrors !== []) {
            $errors['_form'] = $formErrors[0]->getMessage();
        }

        return $errors;
    }
}