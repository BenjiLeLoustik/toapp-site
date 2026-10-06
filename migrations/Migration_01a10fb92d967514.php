<?php

declare(strict_types=1);

namespace Migrations;

use NeoPHP\Package\Orm\Contract\AbstractMigration;

class Migration_01a10fb92d967514 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(): void
    {
        $this->abortIf($this->getPlatformName() !== 'mysql', 'This migration was generated for mysql.');

        $this->addSql('CREATE TABLE `website_page` (`id` INT NOT NULL AUTO_INCREMENT, `created_at` DATETIME NOT NULL, `slug` VARCHAR(150) NOT NULL, `published` TINYINT(1) NOT NULL DEFAULT 1, `updated_at` DATETIME DEFAULT NULL, PRIMARY KEY (`id`)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE UNIQUE INDEX `UNIQ_764646548DADE2A4` ON `website_page` (`slug`)');
        $this->addSql('CREATE TABLE `website_page_translated` (`id` INT NOT NULL AUTO_INCREMENT, `created_at` DATETIME NOT NULL, `locale` VARCHAR(10) NOT NULL, `name` VARCHAR(100) NOT NULL, `content` LONGTEXT NOT NULL, `meta_description` VARCHAR(255) DEFAULT NULL, `page_id` INT NOT NULL, PRIMARY KEY (`id`)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE UNIQUE INDEX `UNIQ_A6F41149A93AD5AC` ON `website_page_translated` (`page_id`, `locale`)');
        $this->addSql('ALTER TABLE `website_page_translated` ADD CONSTRAINT `FK_5BD968FBCD7840DE` FOREIGN KEY (`page_id`) REFERENCES `website_page` (`id`) ON DELETE CASCADE');
    }

    public function down(): void
    {
        $this->abortIf($this->getPlatformName() !== 'mysql', 'This migration was generated for mysql.');

        $this->addSql('ALTER TABLE `website_page_translated` DROP FOREIGN KEY `FK_5BD968FBCD7840DE`');
        $this->addSql('DROP TABLE `website_page`');
        $this->addSql('DROP TABLE `website_page_translated`');
    }
}
