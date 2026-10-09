<?php

declare(strict_types=1);

namespace App\Form\Website;

use NeoPHP\Component\Form\Contract\AbstractForm;
use NeoPHP\Component\Form\Model\FormBuilder;
use NeoPHP\Component\Form\Type\CheckboxType;
use NeoPHP\Component\Form\Type\EmailType;
use NeoPHP\Component\Form\Type\TextareaType;
use NeoPHP\Component\Form\Type\TextType;
use NeoPHP\Component\Form\Type\UrlType;
use NeoPHP\Component\Validator\Constraint\Email;
use NeoPHP\Component\Validator\Constraint\IsTrue;
use NeoPHP\Component\Validator\Constraint\Length;
use NeoPHP\Component\Validator\Constraint\NotBlank;
use NeoPHP\Component\Validator\Constraint\Regex;

class ContactForm extends AbstractForm
{
    public function buildForm(FormBuilder $builder, array $options): void
    {
        $builder->add('lastname', TextType::class, [
            'constraints' => [
                new NotBlank('contact.lastname.not_blank'),
                new Length(max: 50, maxMessage: 'contact.lastname.max_length'),
            ],
        ]);
        $builder->add('firstname', TextType::class, [
            'constraints' => [
                new NotBlank('contact.firstname.not_blank'),
                new Length(max: 50, maxMessage: 'contact.firstname.max_length'),
            ],
        ]);
        $builder->add('email', EmailType::class, [
            'constraints' => [
                new NotBlank('contact.email.not_blank'),
                new Email('contact.email.invalid'),
                new Length(max: 180, maxMessage: 'contact.email.max_length'),
            ],
        ]);
        $builder->add('object', TextType::class, [
            'constraints' => [
                new NotBlank('contact.object.not_blank'),
            ],
        ]);
        $builder->add('message', TextareaType::class, [
            'constraints' => [
                new NotBlank('contact.message.not_blank'),
                new Length(min: 10, max: 5000, minMessage: 'contact.message.min_length', maxMessage: 'contact.message.max_length'),
            ],
        ]);
        $builder->add('website', UrlType::class, [
            'required' => false,
            'constraints' => [
                new Length(max: 255, maxMessage: 'contact.website.max_length'),
                new Regex('#^https?://#i', message: 'contact.website.invalid'),
            ],
        ]);
        $builder->add('consent', CheckboxType::class, [
            'constraints' => [
                new IsTrue('contact.consent.required'),
            ],
        ]);
    }

    public function configureOptions(): array
    {
        return [
            'csrf_protection' => true,
            'csrf_token_id' => 'contact',
        ];
    }
}