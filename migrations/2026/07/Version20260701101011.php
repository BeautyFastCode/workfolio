<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260701101011 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(
            'CREATE TABLE app_statistic_group (
                                id BLOB NOT NULL,
                                position INTEGER NOT NULL,
                                created_at DATETIME NOT NULL,
                                updated_at DATETIME DEFAULT NULL,
                                PRIMARY KEY (id))'
        );
        $this->addSql(
            'CREATE TABLE app_statistic_group_translation (
                                id BLOB NOT NULL,
                                name VARCHAR(255) NOT NULL,
                                locale VARCHAR(5) NOT NULL,
                                created_at DATETIME NOT NULL,
                                updated_at DATETIME DEFAULT NULL,
                                statistic_group_id BLOB NOT NULL,
                                PRIMARY KEY (id),
                                CONSTRAINT FK_B69E87FA6D6D89ED FOREIGN KEY (statistic_group_id) REFERENCES app_statistic_group (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE)'
        );
        $this->addSql('CREATE INDEX IDX_B69E87FA6D6D89ED ON app_statistic_group_translation (statistic_group_id)');
        $this->addSql('CREATE UNIQUE INDEX uniq_statistic_group_translation ON app_statistic_group_translation (statistic_group_id, locale)');

        $this->addSql(
            'CREATE TABLE app_statistic_item (
                                id BLOB NOT NULL,
                                icon VARCHAR(64) DEFAULT NULL,
                                value VARCHAR(32) NOT NULL,
                                position INTEGER NOT NULL,
                                created_at DATETIME NOT NULL,
                                updated_at DATETIME DEFAULT NULL,
                                group_id BLOB NOT NULL,
                                PRIMARY KEY (id),
                                CONSTRAINT FK_C79AF3B9FE54D947 FOREIGN KEY (group_id) REFERENCES app_statistic_group (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE)'
        );
        $this->addSql('CREATE INDEX IDX_C79AF3B9FE54D947 ON app_statistic_item (group_id)');
        $this->addSql(
            'CREATE TABLE app_statistic_item_translation (
                                id BLOB NOT NULL,
                                name VARCHAR(255) NOT NULL,
                                locale VARCHAR(5) NOT NULL,
                                created_at DATETIME NOT NULL,
                                updated_at DATETIME DEFAULT NULL,
                                statistic_item_id BLOB NOT NULL,
                                PRIMARY KEY (id),
                                CONSTRAINT FK_199DE32822C065D3 FOREIGN KEY (statistic_item_id) REFERENCES app_statistic_item (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE)'
        );

        $this->addSql('CREATE INDEX IDX_199DE32822C065D3 ON app_statistic_item_translation (statistic_item_id)');
        $this->addSql('CREATE UNIQUE INDEX uniq_statistic_item_translation ON app_statistic_item_translation (statistic_item_id, locale)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE app_statistic_group');
        $this->addSql('DROP TABLE app_statistic_group_translation');

        $this->addSql('DROP TABLE app_statistic_item');
        $this->addSql('DROP TABLE app_statistic_item_translation');
    }
}
