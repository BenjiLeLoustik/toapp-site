<?php

declare(strict_types=1);

namespace Migrations;

use NeoPHP\Package\Orm\Contract\AbstractMigration;

class Migration_01a102878450b393 extends AbstractMigration
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
        $this->addSql('CREATE TABLE `project_screenshot` (`id` INT NOT NULL AUTO_INCREMENT, `created_at` DATETIME NOT NULL, `title` VARCHAR(100) NOT NULL, `description` VARCHAR(255) NOT NULL, `image` VARCHAR(500) NOT NULL, `position` INT NOT NULL, `project_id` INT NOT NULL, PRIMARY KEY (`id`)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE INDEX `IDX_DA5E2723B076CDB3` ON `project_screenshot` (`project_id`)');
        $this->addSql('ALTER TABLE `project_screenshot` ADD CONSTRAINT `FK_DA5E2723B076CDB3` FOREIGN KEY (`project_id`) REFERENCES `project` (`id`) ON DELETE CASCADE');
    }

    public function down(): void
    {
        $this->abortIf($this->getPlatformName() !== 'mysql', 'This migration was generated for mysql.');

        $this->addSql('ALTER TABLE `project_screenshot` DROP FOREIGN KEY `FK_DA5E2723B076CDB3`');
        $this->addSql('DROP TABLE `project_screenshot`');
        $this->addSql('CREATE TABLE `neo_queue_failed` (`id` BIGINT NOT NULL AUTO_INCREMENT, `queue` VARCHAR(190) NOT NULL, `message_class` VARCHAR(255) NOT NULL, `body` LONGTEXT NOT NULL, `attempts` INT NOT NULL DEFAULT 0, `max_attempts` INT NOT NULL DEFAULT 3, `priority` INT NOT NULL DEFAULT 0, `created_at` BIGINT NOT NULL, `failed_at` BIGINT NOT NULL, `last_error` LONGTEXT DEFAULT NULL, PRIMARY KEY (`id`)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE `neo_queue_jobs` (`id` BIGINT NOT NULL AUTO_INCREMENT, `queue` VARCHAR(190) NOT NULL, `message_class` VARCHAR(255) NOT NULL, `body` LONGTEXT NOT NULL, `attempts` INT NOT NULL DEFAULT 0, `max_attempts` INT NOT NULL DEFAULT 3, `priority` INT NOT NULL DEFAULT 0, `available_at` BIGINT NOT NULL, `reserved_at` BIGINT DEFAULT NULL, `created_at` BIGINT NOT NULL, `last_error` LONGTEXT DEFAULT NULL, PRIMARY KEY (`id`)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE INDEX `neo_queue_jobs_queue_idx` ON `neo_queue_jobs` (`queue`, `reserved_at`, `available_at`)');
    }
}
