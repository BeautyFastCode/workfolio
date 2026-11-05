<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260714110108 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(
            'CREATE TABLE app_block (
                                id BLOB NOT NULL,
                                name VARCHAR(100) NOT NULL,
                                type VARCHAR(50) NOT NULL,
                                position INTEGER NOT NULL,
                                created_at DATETIME NOT NULL,
                                updated_at DATETIME DEFAULT NULL,
                                block_collection_id BLOB NOT NULL,
                                PRIMARY KEY (id),
                                CONSTRAINT FK_55C81AEF2884355F FOREIGN KEY (block_collection_id) REFERENCES app_block_collection (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE)'
        );
        $this->addSql('CREATE INDEX IDX_55C81AEF2884355F ON app_block (block_collection_id)');
        $this->addSql('CREATE UNIQUE INDEX uniq_app_block_name ON app_block (block_collection_id, name)');

        $this->addSql(
            'CREATE TABLE app_block_collection (
                                id BLOB NOT NULL,
                                name VARCHAR(100) NOT NULL,
                                created_at DATETIME NOT NULL,
                                updated_at DATETIME DEFAULT NULL,
                                PRIMARY KEY (id))'
        );
        $this->addSql('CREATE UNIQUE INDEX UNIQ_IDENTIFIER_NAME ON app_block_collection (name)');

        $this->addSql(
            'CREATE TABLE app_block_translation (
                                id BLOB NOT NULL,
                                content CLOB NOT NULL,
                                locale VARCHAR(5) NOT NULL,
                                created_at DATETIME NOT NULL,
                                updated_at DATETIME DEFAULT NULL,
                                block_id BLOB NOT NULL,
                                PRIMARY KEY (id),
                                CONSTRAINT FK_CF21BD2BE9ED820C FOREIGN KEY (block_id) REFERENCES app_block (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE)'
        );
        $this->addSql('CREATE INDEX IDX_CF21BD2BE9ED820C ON app_block_translation (block_id)');
        $this->addSql('CREATE UNIQUE INDEX uniq_block_translation ON app_block_translation (block_id, locale)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE app_block');
        $this->addSql('DROP TABLE app_block_collection');
        $this->addSql('DROP TABLE app_block_translation');
    }
}
