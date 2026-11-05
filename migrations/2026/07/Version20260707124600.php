<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260707124600 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(
            'CREATE TABLE app_page_block (
                                id BLOB NOT NULL,
                                name VARCHAR(100) NOT NULL,
                                type VARCHAR(50) NOT NULL,
                                position INTEGER NOT NULL,
                                created_at DATETIME NOT NULL,
                                updated_at DATETIME DEFAULT NULL,
                                page_id BLOB NOT NULL,
                                PRIMARY KEY (id),
                                CONSTRAINT FK_B381C388C4663E4 FOREIGN KEY (page_id) REFERENCES app_page (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE)'
        );

        $this->addSql('CREATE INDEX IDX_B381C388C4663E4 ON app_page_block (page_id)');
        $this->addSql('CREATE UNIQUE INDEX uniq_app_page_block_name ON app_page_block (page_id, name)');

        $this->addSql(
            'CREATE TABLE app_page_block_translation (
                                id BLOB NOT NULL,
                                content CLOB NOT NULL,
                                locale VARCHAR(5) NOT NULL,
                                created_at DATETIME NOT NULL,
                                updated_at DATETIME DEFAULT NULL,
                                page_block_id BLOB NOT NULL,
                                PRIMARY KEY (id),
                                CONSTRAINT FK_CC7A3A996972852C FOREIGN KEY (page_block_id) REFERENCES app_page_block (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE)'
        );

        $this->addSql('CREATE INDEX IDX_CC7A3A996972852C ON app_page_block_translation (page_block_id)');
        $this->addSql('CREATE UNIQUE INDEX uniq_page_block_translation ON app_page_block_translation (page_block_id, locale)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE app_page_block');
        $this->addSql('DROP TABLE app_page_block_translation');
    }
}
