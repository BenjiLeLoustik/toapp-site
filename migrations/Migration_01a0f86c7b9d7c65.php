<?php

declare(strict_types=1);

namespace Migrations;

use NeoPHP\Package\Orm\Contract\AbstractMigration;

class Migration_01a0f86c7b9d7c65 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(): void
    {
        $this->abortIf($this->getPlatformName() !== 'mysql', 'This migration was generated for mysql.');

        $this->addSql('CREATE TABLE `category_translated` (`name` VARCHAR(100) NOT NULL, `description` LONGTEXT NOT NULL, `id` INT NOT NULL AUTO_INCREMENT, `locale` VARCHAR(10) NOT NULL, `category_id` INT NOT NULL, PRIMARY KEY (`id`)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE UNIQUE INDEX `UNIQ_7846B00E708C9B5C` ON `category_translated` (`category_id`, `locale`)');
        $this->addSql('ALTER TABLE `category` DROP `name`');
        $this->addSql('ALTER TABLE `category` DROP `description`');
        $this->addSql('ALTER TABLE `category_translated` ADD CONSTRAINT `FK_6F4964CCA767F95E` FOREIGN KEY (`category_id`) REFERENCES `category` (`id`)');
    }

    public function down(): void
    {
        $this->abortIf($this->getPlatformName() !== 'mysql', 'This migration was generated for mysql.');

        $this->addSql('ALTER TABLE `category_translated` DROP FOREIGN KEY `FK_6F4964CCA767F95E`');
        $this->addSql('DROP TABLE `category_translated`');
        $this->addSql('ALTER TABLE `category` ADD `name` VARCHAR(100) NOT NULL');
        $this->addSql('ALTER TABLE `category` ADD `description` LONGTEXT NOT NULL');
    }
}
