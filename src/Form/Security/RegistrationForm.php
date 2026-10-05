<?php

declare(strict_types=1);

namespace App\Form\Security;

use NeoPHP\Component\Form\Contract\AbstractForm;
use NeoPHP\Component\Form\FormBuilder;
use NeoPHP\Component\Form\Type\CheckboxType;
use NeoPHP\Component\Form\Type\EmailType;
use NeoPHP\Component\Form\Type\PasswordType;
use NeoPHP\Component\Form\Type\TextType;
use NeoPHP\Component\Validator\Constraint\Email;
use NeoPHP\Component\Validator\Constraint\IsTrue;
use NeoPHP\Component\Validator\Constraint\Length;
use NeoPHP\Component\Validator\Constraint\NotBlank;
use NeoPHP\Component\Validator\Constraint\Regex;

class RegistrationForm extends AbstractForm
{
    public function buildForm(FormBuilder $builder, array $options): void
    {
        $builder
            ->add('firstname', TextType::class, [
                'constraints' => [
                    new NotBlank('Please enter your first name.'),
                    new Length(max: 50),
                ],
            ])
            ->add('lastname', TextType::class, [
                'constraints' => [
                    new NotBlank('Please enter your last name.'),
                    new Length(max: 50),
                ],
            ])
            ->add('username', TextType::class, [
                'constraints' => [
                    new NotBlank('Please choose a username.'),
                    new Length(min: 3, max: 30),
                    new Regex('/^[a-zA-Z0-9_]+$/', message: 'The username can only contain letters, numbers and underscores.'),
                ],
            ])
            ->add('email', EmailType::class, [
                'constraints' => [
                    new NotBlank('Please enter your email address.'),
                    new Email('Please enter a valid email address.'),
                    new Length(max: 180),
                ],
            ])
            ->add('password', PasswordType::class, [
                'constraints' => [
                    new NotBlank('Please choose a password.'),
                    new Length(min: 8, max: 4096, minMessage: 'The password must contain at least {{ limit }} characters.'),
                ],
            ])
            ->add('password_confirmation', PasswordType::class, [
                'mapped' => false,
                'constraints' => [
                    new NotBlank('Please confirm your password.'),
                ],
            ])
            ->add('terms', CheckboxType::class, [
                'mapped' => false,
                'constraints' => [
                    new IsTrue('You must accept the terms of use.'),
                ],
            ]);
    }

    public function configureOptions(): array
    {
        return [
            'csrf_protection' => true,
            'csrf_token_id' => 'register',
        ];
    }
}