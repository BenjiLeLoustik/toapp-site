<?php

declare(strict_types=1);

namespace App\User\Enum;

enum EmailVerificationResultEnum: string
{
    case VERIFIED = 'verified';
    case ALREADY_VERIFIED = 'already_verified';
    case EXPIRED = 'expired';
    case INVALID = 'invalid';

    public function flashType(): string
    {
        return match ($this) {
            self::VERIFIED => 'success',
            self::ALREADY_VERIFIED => 'info',
            self::EXPIRED => 'warning',
            self::INVALID => 'error',
        };
    }

    public function translationKey(): string
    {
        return 'verify_email.flash.' . $this->value;
    }
}