<?php

declare(strict_types=1);

namespace Migrations;

use NeoPHP\Package\Orm\Contract\AbstractMigration;

class Migration_01a1117a1a6b3ba1 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(): void
    {
        $this->abortIf($this->getPlatformName() !== 'mysql', 'This migration was generated for mysql.');

        $this->addSql('CREATE TABLE `user_follow` (`id` INT NOT NULL AUTO_INCREMENT, `created_at` DATETIME NOT NULL, `follower_id` INT NOT NULL, `followed_id` INT NOT NULL, PRIMARY KEY (`id`)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE UNIQUE INDEX `UNIQ_76C51A48FE41398F` ON `user_follow` (`follower_id`, `followed_id`)');
        $this->addSql('CREATE INDEX `IDX_33E78A4E6B000AC0` ON `user_follow` (`followed_id`)');
        $this->addSql('ALTER TABLE `user_follow` ADD CONSTRAINT `FK_C9EDA4FA012342D2` FOREIGN KEY (`follower_id`) REFERENCES `user` (`id`) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE `user_follow` ADD CONSTRAINT `FK_33E78A4E6B000AC0` FOREIGN KEY (`followed_id`) REFERENCES `user` (`id`) ON DELETE CASCADE');
    }

    public function down(): void
    {
        $this->abortIf($this->getPlatformName() !== 'mysql', 'This migration was generated for mysql.');

        $this->addSql('ALTER TABLE `user_follow` DROP FOREIGN KEY `FK_C9EDA4FA012342D2`');
        $this->addSql('ALTER TABLE `user_follow` DROP FOREIGN KEY `FK_33E78A4E6B000AC0`');
        $this->addSql('DROP TABLE `user_follow`');
    }
}
