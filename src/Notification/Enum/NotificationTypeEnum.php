<?php

declare(strict_types=1);

namespace App\Notification\Enum;

use App\Entity\UserNotificationSetting;
use App\User\Enum\UserDigestFrequencyEnum;

enum NotificationTypeEnum: string
{
    case ACTIVITY = 'activity';
    case DIGEST = 'digest';
    case NEWSLETTER = 'newsletter';

    public function unsubscribe(UserNotificationSetting $setting): void
    {
        match ($this) {
            self::ACTIVITY => $setting->setEmailProjectLikes(false)->setEmailProjectFavorites(false),
            self::DIGEST => $setting->setDigestFrequency(UserDigestFrequencyEnum::NEVER),
            self::NEWSLETTER => $setting->setEmailNewsletter(false),
        };
    }

    public function translationKey(): string
    {
        return 'notification.unsubscribe.types.' . $this->value;
    }
}