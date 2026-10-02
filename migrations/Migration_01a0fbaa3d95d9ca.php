<?php

declare(strict_types=1);

namespace Migrations;

use NeoPHP\Package\Orm\Contract\AbstractMigration;

class Migration_01a0fbaa3d95d9ca extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(): void
    {
        $this->abortIf($this->getPlatformName() !== 'mysql', 'This migration was generated for mysql.');

        $this->addSql('CREATE TABLE `project_like` (`id` INT NOT NULL AUTO_INCREMENT, `created_at` DATETIME NOT NULL, `project_id` INT NOT NULL, PRIMARY KEY (`id`)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE INDEX `IDX_7142C8DE6594B1F5` ON `project_like` (`project_id`)');
        $this->addSql('ALTER TABLE `project_like` ADD CONSTRAINT `FK_7142C8DE6594B1F5` FOREIGN KEY (`project_id`) REFERENCES `project` (`id`)');
    }

    public function down(): void
    {
        $this->abortIf($this->getPlatformName() !== 'mysql', 'This migration was generated for mysql.');

        $this->addSql('ALTER TABLE `project_like` DROP FOREIGN KEY `FK_7142C8DE6594B1F5`');
        $this->addSql('DROP TABLE `project_like`');
    }
}
