<?php

declare(strict_types=1);

namespace Migrations;

use NeoPHP\Package\Orm\Contract\AbstractMigration;

class Migration_01a110925f3d51bf extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(): void
    {
        $this->abortIf($this->getPlatformName() !== 'mysql', 'This migration was generated for mysql.');

        $this->addSql('ALTER TABLE `project_view` ADD `source` VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE `project_view` ADD `country` VARCHAR(2) DEFAULT NULL');
    }

    public function down(): void
    {
        $this->abortIf($this->getPlatformName() !== 'mysql', 'This migration was generated for mysql.');

        $this->addSql('ALTER TABLE `project_view` DROP `source`');
        $this->addSql('ALTER TABLE `project_view` DROP `country`');
    }
}
