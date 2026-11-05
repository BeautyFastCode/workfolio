<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260702112543 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(
            'CREATE TABLE app_technology_group (
                                id BLOB NOT NULL,
                                highlighted BOOLEAN DEFAULT 0 NOT NULL,
                                position INTEGER NOT NULL,
                                created_at DATETIME NOT NULL,
                                updated_at DATETIME DEFAULT NULL,
                                PRIMARY KEY (id))'
        );
        $this->addSql(
            'CREATE TABLE app_technology_group_translation (
                                id BLOB NOT NULL,
                                name VARCHAR(255) NOT NULL,
                                locale VARCHAR(5) NOT NULL,
                                created_at DATETIME NOT NULL,
                                updated_at DATETIME DEFAULT NULL,
                                technology_group_id BLOB NOT NULL,
                                PRIMARY KEY (id),
                                CONSTRAINT FK_BCFC6C23DC1781BB FOREIGN KEY (technology_group_id) REFERENCES app_technology_group (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE)'
        );

        $this->addSql('CREATE INDEX IDX_BCFC6C23DC1781BB ON app_technology_group_translation (technology_group_id)');
        $this->addSql('CREATE UNIQUE INDEX uniq_technology_group_translation ON app_technology_group_translation (technology_group_id, locale)');

        $this->addSql(
            'CREATE TABLE app_technology_item (
                                id BLOB NOT NULL,
                                name VARCHAR(255) NOT NULL,
                                icon VARCHAR(64) DEFAULT NULL,
                                position INTEGER NOT NULL,
                                created_at DATETIME NOT NULL,
                                updated_at DATETIME DEFAULT NULL,
                                group_id BLOB NOT NULL,
                                PRIMARY KEY (id),
                                CONSTRAINT FK_E3C27A9DFE54D947 FOREIGN KEY (group_id) REFERENCES app_technology_group (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE)'
        );
        $this->addSql('CREATE INDEX IDX_E3C27A9DFE54D947 ON app_technology_item (group_id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE app_technology_group');
        $this->addSql('DROP TABLE app_technology_group_translation');
        $this->addSql('DROP TABLE app_technology_item');
    }
}
