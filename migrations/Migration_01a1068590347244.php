<?php

declare(strict_types=1);

namespace Migrations;

use NeoPHP\Package\Orm\Contract\AbstractMigration;

class Migration_01a1068590347244 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(): void
    {
        $this->abortIf($this->getPlatformName() !== 'mysql', 'This migration was generated for mysql.');

        $this->addSql('CREATE TABLE `project_favorite` (`id` INT NOT NULL AUTO_INCREMENT, `created_at` DATETIME NOT NULL, `project_id` INT NOT NULL, `user_id` INT NOT NULL, PRIMARY KEY (`id`)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE UNIQUE INDEX `UNIQ_931178447A2134B3` ON `project_favorite` (`user_id`, `project_id`)');
        $this->addSql('CREATE INDEX `IDX_EB4BC6EB3513ABDC` ON `project_favorite` (`project_id`)');
        $this->addSql('ALTER TABLE `user` MODIFY `avatar` VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE `project_favorite` ADD CONSTRAINT `FK_EB4BC6EB3513ABDC` FOREIGN KEY (`project_id`) REFERENCES `project` (`id`) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE `project_favorite` ADD CONSTRAINT `FK_C522B52D272137E4` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`) ON DELETE CASCADE');
    }

    public function down(): void
    {
        $this->abortIf($this->getPlatformName() !== 'mysql', 'This migration was generated for mysql.');

        $this->addSql('ALTER TABLE `project_favorite` DROP FOREIGN KEY `FK_EB4BC6EB3513ABDC`');
        $this->addSql('ALTER TABLE `project_favorite` DROP FOREIGN KEY `FK_C522B52D272137E4`');
        $this->addSql('DROP TABLE `project_favorite`');
        $this->addSql('ALTER TABLE `user` MODIFY `avatar` VARCHAR(255) NOT NULL');
    }
}
