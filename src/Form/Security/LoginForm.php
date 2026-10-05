<?php

declare(strict_types=1);

namespace App\Form\Security;

use NeoPHP\Component\Form\Contract\AbstractForm;
use NeoPHP\Component\Form\FormBuilder;

class LoginForm extends AbstractForm
{
    public function buildForm(FormBuilder $builder, array $options): void
    {
    }

    public function configureOptions(): array
    {
        return [
            'csrf_protection' => true,
            'csrf_field_name' => '_csrf_token',
            'csrf_token_id' => 'authenticate',
        ];
    }
}