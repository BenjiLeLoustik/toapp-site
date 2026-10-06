<?php

declare(strict_types=1);

namespace Migrations;

use NeoPHP\Package\Orm\Contract\AbstractMigration;

class Migration_01a0fbdb8f5e6434 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(): void
    {
        $this->abortIf($this->getPlatformName() !== 'mysql', 'This migration was generated for mysql.');

        $this->addSql('ALTER TABLE `user` ADD `username` VARCHAR(255) NOT NULL');
        $this->addSql('CREATE UNIQUE INDEX `UNIQ_739BABD74995A117` ON `user` (`username`)');
    }

    public function down(): void
    {
        $this->abortIf($this->getPlatformName() !== 'mysql', 'This migration was generated for mysql.');

        $this->addSql('DROP INDEX `UNIQ_739BABD74995A117` ON `user`');
        $this->addSql('ALTER TABLE `user` DROP `username`');
    }
}
