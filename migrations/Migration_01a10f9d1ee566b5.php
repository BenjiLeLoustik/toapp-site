<?php

declare(strict_types=1);

namespace Migrations;

use NeoPHP\Package\Orm\Contract\AbstractMigration;

class Migration_01a10f9d1ee566b5 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(): void
    {
        $this->abortIf($this->getPlatformName() !== 'mysql', 'This migration was generated for mysql.');

        $this->addSql('CREATE TABLE `website_contact_config` (`id` INT NOT NULL AUTO_INCREMENT, `created_at` DATETIME NOT NULL, `value` VARCHAR(255) NOT NULL, `enabled` TINYINT(1) NOT NULL DEFAULT 1, `type_id` INT NOT NULL, PRIMARY KEY (`id`)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE INDEX `IDX_4D70F8F2149008FD` ON `website_contact_config` (`type_id`)');
        $this->addSql('CREATE TABLE `website_contact_message` (`id` INT NOT NULL AUTO_INCREMENT, `created_at` DATETIME NOT NULL, `lastname` VARCHAR(50) NOT NULL, `firstname` VARCHAR(50) NOT NULL, `email` VARCHAR(180) NOT NULL, `message` LONGTEXT NOT NULL, `website` VARCHAR(255) DEFAULT NULL, `status` VARCHAR(255) NOT NULL, `consent` TINYINT(1) NOT NULL DEFAULT 0, `object_id` INT NOT NULL, PRIMARY KEY (`id`)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE INDEX `IDX_8A92FD2CB80831A4` ON `website_contact_message` (`object_id`)');
        $this->addSql('CREATE TABLE `website_contact_object` (`id` INT NOT NULL AUTO_INCREMENT, `created_at` DATETIME NOT NULL, `slug` VARCHAR(100) NOT NULL, PRIMARY KEY (`id`)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE UNIQUE INDEX `UNIQ_0E79D3330D8E6066` ON `website_contact_object` (`slug`)');
        $this->addSql('CREATE TABLE `website_contact_object_translated` (`id` INT NOT NULL AUTO_INCREMENT, `created_at` DATETIME NOT NULL, `locale` VARCHAR(10) NOT NULL, `name` VARCHAR(100) NOT NULL, `object_id` INT NOT NULL, PRIMARY KEY (`id`)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE UNIQUE INDEX `UNIQ_B42C0CB0471E7493` ON `website_contact_object_translated` (`object_id`, `locale`)');
        $this->addSql('CREATE TABLE `website_contact_type` (`id` INT NOT NULL AUTO_INCREMENT, `created_at` DATETIME NOT NULL, `slug` VARCHAR(100) NOT NULL, `icon` VARCHAR(50) NOT NULL, PRIMARY KEY (`id`)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE UNIQUE INDEX `UNIQ_B203EAE4598FDB4A` ON `website_contact_type` (`slug`)');
        $this->addSql('CREATE TABLE `website_contact_type_translated` (`id` INT NOT NULL AUTO_INCREMENT, `created_at` DATETIME NOT NULL, `locale` VARCHAR(10) NOT NULL, `name` VARCHAR(100) NOT NULL, `type_id` INT NOT NULL, PRIMARY KEY (`id`)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE UNIQUE INDEX `UNIQ_626768C97F4D8B99` ON `website_contact_type_translated` (`type_id`, `locale`)');
        $this->addSql('ALTER TABLE `website_contact_config` ADD CONSTRAINT `FK_4D70F8F2149008FD` FOREIGN KEY (`type_id`) REFERENCES `website_contact_type` (`id`) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE `website_contact_message` ADD CONSTRAINT `FK_8A92FD2CB80831A4` FOREIGN KEY (`object_id`) REFERENCES `website_contact_object` (`id`)');
        $this->addSql('ALTER TABLE `website_contact_object_translated` ADD CONSTRAINT `FK_64AC6FF2F320E0F7` FOREIGN KEY (`object_id`) REFERENCES `website_contact_object` (`id`) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE `website_contact_type_translated` ADD CONSTRAINT `FK_948D42A8005EDFC3` FOREIGN KEY (`type_id`) REFERENCES `website_contact_type` (`id`) ON DELETE CASCADE');
    }

    public function down(): void
    {
        $this->abortIf($this->getPlatformName() !== 'mysql', 'This migration was generated for mysql.');

        $this->addSql('ALTER TABLE `website_contact_config` DROP FOREIGN KEY `FK_4D70F8F2149008FD`');
        $this->addSql('ALTER TABLE `website_contact_message` DROP FOREIGN KEY `FK_8A92FD2CB80831A4`');
        $this->addSql('ALTER TABLE `website_contact_object_translated` DROP FOREIGN KEY `FK_64AC6FF2F320E0F7`');
        $this->addSql('ALTER TABLE `website_contact_type_translated` DROP FOREIGN KEY `FK_948D42A8005EDFC3`');
        $this->addSql('DROP TABLE `website_contact_config`');
        $this->addSql('DROP TABLE `website_contact_message`');
        $this->addSql('DROP TABLE `website_contact_object`');
        $this->addSql('DROP TABLE `website_contact_object_translated`');
        $this->addSql('DROP TABLE `website_contact_type`');
        $this->addSql('DROP TABLE `website_contact_type_translated`');
    }
}
