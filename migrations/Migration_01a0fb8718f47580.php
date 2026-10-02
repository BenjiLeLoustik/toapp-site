<?php

declare(strict_types=1);

namespace Migrations;

use NeoPHP\Package\Orm\Contract\AbstractMigration;

class Migration_01a0fb8718f47580 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(): void
    {
        $this->abortIf($this->getPlatformName() !== 'mysql', 'This migration was generated for mysql.');

        $this->addSql('CREATE TABLE `project` (`id` INT NOT NULL AUTO_INCREMENT, `name` VARCHAR(150) NOT NULL, `slug` VARCHAR(200) NOT NULL, `cover` VARCHAR(255) DEFAULT NULL, `description` LONGTEXT DEFAULT NULL, `created_at` DATETIME NOT NULL, `category_id` INT DEFAULT NULL, PRIMARY KEY (`id`)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE UNIQUE INDEX `UNIQ_2AEA9B25949A4440` ON `project` (`slug`)');
        $this->addSql('CREATE INDEX `IDX_9D4D06A8061FD6AD` ON `project` (`category_id`)');
        $this->addSql('CREATE TABLE `technology` (`id` INT NOT NULL AUTO_INCREMENT, `name` VARCHAR(100) NOT NULL, `slug` VARCHAR(150) NOT NULL, `created_at` DATETIME NOT NULL, PRIMARY KEY (`id`)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE UNIQUE INDEX `UNIQ_51686740D14C1784` ON `technology` (`slug`)');
        $this->addSql('CREATE TABLE `project_technology` (`project_id` INT NOT NULL, `technology_id` INT NOT NULL, PRIMARY KEY (`project_id`, `technology_id`)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE INDEX `IDX_DE5DEA72D443DB98` ON `project_technology` (`technology_id`)');
        $this->addSql('ALTER TABLE `project` ADD CONSTRAINT `FK_9D4D06A8061FD6AD` FOREIGN KEY (`category_id`) REFERENCES `category` (`id`)');
        $this->addSql('ALTER TABLE `project_technology` ADD CONSTRAINT `FK_CB17355FDE63FCA5` FOREIGN KEY (`project_id`) REFERENCES `project` (`id`) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE `project_technology` ADD CONSTRAINT `FK_DE5DEA72D443DB98` FOREIGN KEY (`technology_id`) REFERENCES `technology` (`id`) ON DELETE CASCADE');
    }

    public function down(): void
    {
        $this->abortIf($this->getPlatformName() !== 'mysql', 'This migration was generated for mysql.');

        $this->addSql('ALTER TABLE `project` DROP FOREIGN KEY `FK_9D4D06A8061FD6AD`');
        $this->addSql('ALTER TABLE `project_technology` DROP FOREIGN KEY `FK_CB17355FDE63FCA5`');
        $this->addSql('ALTER TABLE `project_technology` DROP FOREIGN KEY `FK_DE5DEA72D443DB98`');
        $this->addSql('DROP TABLE `project`');
        $this->addSql('DROP TABLE `technology`');
        $this->addSql('DROP TABLE `project_technology`');
    }
}
