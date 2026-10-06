<?php

declare(strict_types=1);

namespace App\Email;

use NeoPHP\Component\Mailer\Mime\Email;

class VerifyEmail extends Email
{
    public function __construct(string $to, string $subject, string $html)
    {
        $this->to($to)
            ->subject($subject)
            ->html($html);
    }
}