<?php

declare(strict_types=1);

namespace Migrations;

use NeoPHP\Package\Orm\Contract\AbstractMigration;

class Migration_01a0fbdac49960a0 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(): void
    {
        $this->abortIf($this->getPlatformName() !== 'mysql', 'This migration was generated for mysql.');

        $this->addSql('ALTER TABLE `user` ADD `firstname` VARCHAR(50) NOT NULL');
        $this->addSql('ALTER TABLE `user` ADD `lastname` VARCHAR(50) NOT NULL');
        $this->addSql('ALTER TABLE `user` ADD `slug` VARCHAR(150) NOT NULL');
        $this->addSql('ALTER TABLE `user` ADD `avatar` VARCHAR(255) NOT NULL');
        $this->addSql('CREATE UNIQUE INDEX `UNIQ_D4E99572111A8CE3` ON `user` (`slug`)');
    }

    public function down(): void
    {
        $this->abortIf($this->getPlatformName() !== 'mysql', 'This migration was generated for mysql.');

        $this->addSql('DROP INDEX `UNIQ_D4E99572111A8CE3` ON `user`');
        $this->addSql('ALTER TABLE `user` DROP `firstname`');
        $this->addSql('ALTER TABLE `user` DROP `lastname`');
        $this->addSql('ALTER TABLE `user` DROP `slug`');
        $this->addSql('ALTER TABLE `user` DROP `avatar`');
    }
}
