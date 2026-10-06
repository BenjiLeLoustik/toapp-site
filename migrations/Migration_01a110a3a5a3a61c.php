<?php

declare(strict_types=1);

namespace Migrations;

use NeoPHP\Package\Orm\Contract\AbstractMigration;

class Migration_01a110a3a5a3a61c extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(): void
    {
        $this->abortIf($this->getPlatformName() !== 'mysql', 'This migration was generated for mysql.');

        $this->addSql('ALTER TABLE `project` ADD `website_url` VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE `project` ADD `repository_url` VARCHAR(255) DEFAULT NULL');
    }

    public function down(): void
    {
        $this->abortIf($this->getPlatformName() !== 'mysql', 'This migration was generated for mysql.');

        $this->addSql('ALTER TABLE `project` DROP `website_url`');
        $this->addSql('ALTER TABLE `project` DROP `repository_url`');
    }
}
