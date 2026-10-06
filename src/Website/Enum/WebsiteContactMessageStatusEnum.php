<?php

namespace App\Website\Enum;

enum WebsiteContactMessageStatusEnum: string
{
    case NEW = 'new';
    case READ = 'read';
    case ANSWERED = 'answered';
    case ARCHIVED = 'archived';

    public function label(): string
    {
        return match ($this) {
            self::NEW => 'New',
            self::READ => 'Read',
            self::ANSWERED => 'Answered',
            self::ARCHIVED => 'Archived',
        };
    }
}
