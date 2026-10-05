<?php

declare(strict_types=1);

namespace App\Security\Helper;

use App\Entity\User;
use NeoPHP\Component\Form\Contract\FormInterface;
use NeoPHP\Package\Orm\Contract\EntityManagerInterface;
use NeoPHP\Package\Security\Hasher\UserPasswordHasher;

class RegistrationHelper
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private UserPasswordHasher $passwordHasher,
    ) {
    }

    public function validate(FormInterface $form): bool
    {
        $repository = $this->entityManager->getRepository(User::class);

        if ($form->get('password')->getData() !== $form->get('password_confirmation')->getData()) {
            $form->get('password_confirmation')->addError('The passwords do not match.');
        }

        if ($repository->findOneBy(['email' => $form->get('email')->getData()]) !== null) {
            $form->get('email')->addError('An account already exists with this email address.');
        }

        if ($repository->findOneBy(['username' => $form->get('username')->getData()]) !== null) {
            $form->get('username')->addError('This username is already taken.');
        }

        return $form->getErrors(true) === [];
    }

    public function register(FormInterface $form): User
    {
        $username = (string) $form->get('username')->getData();

        $user = new User();
        $user
            ->setFirstname(trim((string) $form->get('firstname')->getData()))
            ->setLastname(trim((string) $form->get('lastname')->getData()))
            ->setUsername($username)
            ->setSlug(strtolower($username))
            ->setEmail(strtolower(trim((string) $form->get('email')->getData())));

        $user->setPassword($this->passwordHasher->hashPassword($user, (string) $form->get('password')->getData()));

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        return $user;
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