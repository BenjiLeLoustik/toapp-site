<?php

declare(strict_types=1);

namespace App\User\Listener;

use App\User\Event\UserRegisteredEvent;
use App\User\Helper\EmailVerificationHelper;
use NeoPHP\Component\Event\Attribute\AsListener;
use NeoPHP\Component\Logger\Contract\LoggerInterface;

#[AsListener]
class SendVerificationEmailListener
{
    public function __construct(
        private EmailVerificationHelper $verification,
        private LoggerInterface $logger,
    ) {
    }

    public function __invoke(UserRegisteredEvent $event): void
    {
        try {
            $this->verification->send($event->user);
        } catch (\Throwable $exception) {
            $this->logger->error('The verification email of the user #{id} could not be sent: {message}', [
                'id' => $event->user->getId(),
                'message' => $exception->getMessage(),
            ]);
        }
    }
}