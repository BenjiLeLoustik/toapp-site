<?php

declare(strict_types=1);

namespace Migrations;

use NeoPHP\Package\Orm\Contract\AbstractMigration;

class Migration_01a112319c039c35 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(): void
    {
        $this->abortIf($this->getPlatformName() !== 'mysql', 'This migration was generated for mysql.');

        $this->addSql('ALTER TABLE `user` ADD `email_verified_at` DATETIME DEFAULT NULL');
        $this->addSql('ALTER TABLE `user` ADD `verification_sent_at` DATETIME DEFAULT NULL');
    }

    public function down(): void
    {
        $this->abortIf($this->getPlatformName() !== 'mysql', 'This migration was generated for mysql.');

        $this->addSql('ALTER TABLE `user` DROP `email_verified_at`');
        $this->addSql('ALTER TABLE `user` DROP `verification_sent_at`');
    }
}
