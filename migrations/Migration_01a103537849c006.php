<?php

declare(strict_types=1);

namespace Migrations;

use NeoPHP\Package\Orm\Contract\AbstractMigration;

class Migration_01a103537849c006 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(): void
    {
        $this->abortIf($this->getPlatformName() !== 'mysql', 'This migration was generated for mysql.');

        $this->addSql('CREATE TABLE `project_view` (`id` INT NOT NULL AUTO_INCREMENT, `created_at` DATETIME NOT NULL, `ip` VARCHAR(45) DEFAULT NULL, `project_id` INT NOT NULL, `user_id` INT DEFAULT NULL, PRIMARY KEY (`id`)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE INDEX `IDX_D9B95AF116C340C7` ON `project_view` (`project_id`)');
        $this->addSql('CREATE INDEX `IDX_FEE84ADE39B11295` ON `project_view` (`user_id`)');
        $this->addSql('ALTER TABLE `project_view` ADD CONSTRAINT `FK_D9B95AF116C340C7` FOREIGN KEY (`project_id`) REFERENCES `project` (`id`)');
        $this->addSql('ALTER TABLE `project_view` ADD CONSTRAINT `FK_FEE84ADE39B11295` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`)');
    }

    public function down(): void
    {
        $this->abortIf($this->getPlatformName() !== 'mysql', 'This migration was generated for mysql.');

        $this->addSql('ALTER TABLE `project_view` DROP FOREIGN KEY `FK_D9B95AF116C340C7`');
        $this->addSql('ALTER TABLE `project_view` DROP FOREIGN KEY `FK_FEE84ADE39B11295`');
        $this->addSql('DROP TABLE `project_view`');
    }
}