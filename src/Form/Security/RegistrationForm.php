<?php

declare(strict_types=1);

namespace App\Form\Security;

use NeoPHP\Component\Form\Contract\AbstractForm;
use NeoPHP\Component\Form\Model\FormBuilder;
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
                    new NotBlank('register.firstname.not_blank'),
                    new Length(max: 50, maxMessage: 'register.firstname.max_length'),
                ],
            ])
            ->add('lastname', TextType::class, [
                'constraints' => [
                    new NotBlank('register.lastname.not_blank'),
                    new Length(max: 50, maxMessage: 'register.lastname.max_length'),
                ],
            ])
            ->add('username', TextType::class, [
                'constraints' => [
                    new NotBlank('register.username.not_blank'),
                    new Length(min: 3, max: 30, minMessage: 'register.username.min_length', maxMessage: 'register.username.max_length'),
                    new Regex('/^[a-zA-Z0-9_]+$/', message: 'register.username.format'),
                ],
            ])
            ->add('email', EmailType::class, [
                'constraints' => [
                    new NotBlank('register.email.not_blank'),
                    new Email('register.email.invalid'),
                    new Length(max: 180, maxMessage: 'register.email.max_length'),
                ],
            ])
            ->add('password', PasswordType::class, [
                'constraints' => [
                    new NotBlank('register.password.not_blank'),
                    new Length(min: 8, max: 4096, minMessage: 'register.password.min_length', maxMessage: 'register.password.max_length'),
                ],
            ])
            ->add('password_confirmation', PasswordType::class, [
                'mapped' => false,
                'constraints' => [
                    new NotBlank('register.password_confirmation.not_blank'),
                ],
            ])
            ->add('terms', CheckboxType::class, [
                'mapped' => false,
                'constraints' => [
                    new IsTrue('register.terms.required'),
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