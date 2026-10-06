<?php

declare(strict_types=1);

namespace Migrations;

use NeoPHP\Package\Orm\Contract\AbstractMigration;

class Migration_01a11254f37e2e49 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(): void
    {
        $this->abortIf($this->getPlatformName() !== 'mysql', 'This migration was generated for mysql.');

        $this->addSql('ALTER TABLE `user_notification_setting` ADD `activity_notified_at` DATETIME DEFAULT NULL');
        $this->addSql('ALTER TABLE `user_notification_setting` ADD `digest_sent_at` DATETIME DEFAULT NULL');
    }

    public function down(): void
    {
        $this->abortIf($this->getPlatformName() !== 'mysql', 'This migration was generated for mysql.');

        $this->addSql('ALTER TABLE `user_notification_setting` DROP `activity_notified_at`');
        $this->addSql('ALTER TABLE `user_notification_setting` DROP `digest_sent_at`');
    }
}
