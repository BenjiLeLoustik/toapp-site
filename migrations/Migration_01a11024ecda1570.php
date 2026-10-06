<?php

declare(strict_types=1);

namespace Migrations;

use NeoPHP\Package\Orm\Contract\AbstractMigration;

class Migration_01a11024ecda1570 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(): void
    {
        $this->abortIf($this->getPlatformName() !== 'mysql', 'This migration was generated for mysql.');

        $this->addSql('CREATE TABLE `user_login_history` (`id` INT NOT NULL AUTO_INCREMENT, `created_at` DATETIME NOT NULL, `ip` VARCHAR(45) DEFAULT NULL, `user_agent` VARCHAR(255) DEFAULT NULL, `method` VARCHAR(30) NOT NULL, `user_id` INT NOT NULL, PRIMARY KEY (`id`)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE INDEX `IDX_9643B1D408C3F172` ON `user_login_history` (`user_id`)');
        $this->addSql('CREATE TABLE `user_notification_setting` (`id` INT NOT NULL AUTO_INCREMENT, `created_at` DATETIME NOT NULL, `email_project_likes` TINYINT(1) NOT NULL DEFAULT 1, `email_project_favorites` TINYINT(1) NOT NULL DEFAULT 1, `email_newsletter` TINYINT(1) NOT NULL DEFAULT 0, `digest_frequency` VARCHAR(255) NOT NULL, `user_id` INT NOT NULL, PRIMARY KEY (`id`)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE UNIQUE INDEX `UNIQ_57A7CA1C91E9BE71` ON `user_notification_setting` (`user_id`)');
        $this->addSql('CREATE TABLE `user_preference` (`id` INT NOT NULL AUTO_INCREMENT, `created_at` DATETIME NOT NULL, `theme` VARCHAR(20) DEFAULT NULL, `accent` VARCHAR(20) DEFAULT NULL, `scale` VARCHAR(20) DEFAULT NULL, `locale` VARCHAR(10) DEFAULT NULL, `date_format` VARCHAR(255) NOT NULL, `timezone` VARCHAR(64) NOT NULL, `number_format` VARCHAR(255) NOT NULL, `user_id` INT NOT NULL, PRIMARY KEY (`id`)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE UNIQUE INDEX `UNIQ_F9EF0926F1F87968` ON `user_preference` (`user_id`)');
        $this->addSql('CREATE TABLE `user_privacy_setting` (`id` INT NOT NULL AUTO_INCREMENT, `created_at` DATETIME NOT NULL, `profile_public` TINYINT(1) NOT NULL DEFAULT 1, `show_email` TINYINT(1) NOT NULL DEFAULT 0, `show_location` TINYINT(1) NOT NULL DEFAULT 1, `show_stats` TINYINT(1) NOT NULL DEFAULT 1, `user_id` INT NOT NULL, PRIMARY KEY (`id`)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE UNIQUE INDEX `UNIQ_F5BD2483D76CD476` ON `user_privacy_setting` (`user_id`)');
        $this->addSql('ALTER TABLE `user` ADD `location` VARCHAR(100) DEFAULT NULL');
        $this->addSql('ALTER TABLE `user` ADD `website` VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE `user` ADD `last_login_at` DATETIME DEFAULT NULL');
        $this->addSql('ALTER TABLE `user` ADD `deactivated_at` DATETIME DEFAULT NULL');
        $this->addSql('ALTER TABLE `user` ADD `deleted_at` DATETIME DEFAULT NULL');
        $this->addSql('ALTER TABLE `user_login_history` ADD CONSTRAINT `FK_9643B1D408C3F172` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE `user_notification_setting` ADD CONSTRAINT `FK_57A7CA1C91E9BE71` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE `user_preference` ADD CONSTRAINT `FK_F9EF0926F1F87968` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE `user_privacy_setting` ADD CONSTRAINT `FK_F5BD2483D76CD476` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`) ON DELETE CASCADE');
    }

    public function down(): void
    {
        $this->abortIf($this->getPlatformName() !== 'mysql', 'This migration was generated for mysql.');

        $this->addSql('ALTER TABLE `user_login_history` DROP FOREIGN KEY `FK_9643B1D408C3F172`');
        $this->addSql('ALTER TABLE `user_notification_setting` DROP FOREIGN KEY `FK_57A7CA1C91E9BE71`');
        $this->addSql('ALTER TABLE `user_preference` DROP FOREIGN KEY `FK_F9EF0926F1F87968`');
        $this->addSql('ALTER TABLE `user_privacy_setting` DROP FOREIGN KEY `FK_F5BD2483D76CD476`');
        $this->addSql('DROP TABLE `user_login_history`');
        $this->addSql('DROP TABLE `user_notification_setting`');
        $this->addSql('DROP TABLE `user_preference`');
        $this->addSql('DROP TABLE `user_privacy_setting`');
        $this->addSql('ALTER TABLE `user` DROP `location`');
        $this->addSql('ALTER TABLE `user` DROP `website`');
        $this->addSql('ALTER TABLE `user` DROP `last_login_at`');
        $this->addSql('ALTER TABLE `user` DROP `deactivated_at`');
        $this->addSql('ALTER TABLE `user` DROP `deleted_at`');
    }
}
