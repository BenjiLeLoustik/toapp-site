<?php

declare(strict_types=1);

namespace App\User\Helper;

use App\Entity\User;
use NeoPHP\Component\Container\Attribute\Autowire;

class UserPathHelper
{
    public const ROOT = 'user';

    public const AVATAR = 'avatar';

    public function __construct(
        #[Autowire(env: 'APP_SECRET')]
        private string $secret,
    ) {
    }

    public function encryptedId(User $user): string
    {
        return substr(hash_hmac('sha256', (string) $user->getId(), $this->secret), 0, 32);
    }

    public function directory(User $user, string $subdirectory = ''): string
    {
        $directory = self::ROOT . '/' . $this->encryptedId($user);

        return $subdirectory !== '' ? $directory . '/' . trim($subdirectory, '/') : $directory;
    }

    public function avatarDirectory(User $user): string
    {
        return $this->directory($user, self::AVATAR);
    }
}