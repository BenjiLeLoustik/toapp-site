<?php

declare(strict_types=1);

namespace Migrations;

use NeoPHP\Package\Orm\Contract\AbstractMigration;

class Migration_01a101f73d238ca8 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(): void
    {
        $this->abortIf($this->getPlatformName() !== 'mysql', 'This migration was generated for mysql.');

        $this->addSql('DROP TABLE `neo_queue_failed`');
        $this->addSql('DROP TABLE `neo_queue_jobs`');
        $this->addSql('ALTER TABLE `project` ADD `visibility` VARCHAR(255) NOT NULL');
    }

    public function down(): void
    {
        $this->abortIf($this->getPlatformName() !== 'mysql', 'This migration was generated for mysql.');

        $this->addSql('CREATE TABLE `neo_queue_failed` (`id` BIGINT NOT NULL AUTO_INCREMENT, `queue` VARCHAR(190) NOT NULL, `message_class` VARCHAR(255) NOT NULL, `body` LONGTEXT NOT NULL, `attempts` INT NOT NULL DEFAULT 0, `max_attempts` INT NOT NULL DEFAULT 3, `priority` INT NOT NULL DEFAULT 0, `created_at` BIGINT NOT NULL, `failed_at` BIGINT NOT NULL, `last_error` LONGTEXT DEFAULT NULL, PRIMARY KEY (`id`)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE `neo_queue_jobs` (`id` BIGINT NOT NULL AUTO_INCREMENT, `queue` VARCHAR(190) NOT NULL, `message_class` VARCHAR(255) NOT NULL, `body` LONGTEXT NOT NULL, `attempts` INT NOT NULL DEFAULT 0, `max_attempts` INT NOT NULL DEFAULT 3, `priority` INT NOT NULL DEFAULT 0, `available_at` BIGINT NOT NULL, `reserved_at` BIGINT DEFAULT NULL, `created_at` BIGINT NOT NULL, `last_error` LONGTEXT DEFAULT NULL, PRIMARY KEY (`id`)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE INDEX `neo_queue_jobs_queue_idx` ON `neo_queue_jobs` (`queue`, `reserved_at`, `available_at`)');
        $this->addSql('ALTER TABLE `project` DROP `visibility`');
    }
}
