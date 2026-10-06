<?php

declare(strict_types=1);

namespace Migrations;

use NeoPHP\Package\Orm\Contract\AbstractMigration;

class Migration_01a0fbcfb5649033 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(): void
    {
        $this->abortIf($this->getPlatformName() !== 'mysql', 'This migration was generated for mysql.');

        $this->addSql('ALTER TABLE `project` ADD `user_id` INT NOT NULL');
        $this->addSql('CREATE INDEX `IDX_C56A979969B8DA4D` ON `project` (`user_id`)');
        $this->addSql('ALTER TABLE `project_like` ADD `user_id` INT NOT NULL');
        $this->addSql('CREATE UNIQUE INDEX `UNIQ_532E0BAAD30BCFEC` ON `project_like` (`user_id`, `project_id`)');
        $this->addSql('ALTER TABLE `user` ADD `created_at` DATETIME NOT NULL');
        $this->addSql('ALTER TABLE `project` ADD CONSTRAINT `FK_C56A979969B8DA4D` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`)');
        $this->addSql('ALTER TABLE `project_like` ADD CONSTRAINT `FK_FA47CD0B2811F139` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`)');
    }

    public function down(): void
    {
        $this->abortIf($this->getPlatformName() !== 'mysql', 'This migration was generated for mysql.');

        $this->addSql('ALTER TABLE `project` DROP FOREIGN KEY `FK_C56A979969B8DA4D`');
        $this->addSql('ALTER TABLE `project_like` DROP FOREIGN KEY `FK_FA47CD0B2811F139`');
        $this->addSql('DROP INDEX `IDX_C56A979969B8DA4D` ON `project`');
        $this->addSql('ALTER TABLE `project` DROP `user_id`');
        $this->addSql('DROP INDEX `UNIQ_532E0BAAD30BCFEC` ON `project_like`');
        $this->addSql('ALTER TABLE `project_like` DROP `user_id`');
        $this->addSql('ALTER TABLE `user` DROP `created_at`');
    }
}
