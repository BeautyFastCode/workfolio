<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260707093459 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(
            'CREATE TABLE app_page (
                                id BLOB NOT NULL,
                                content_template VARCHAR(255) NOT NULL,
                                published BOOLEAN DEFAULT 0 NOT NULL,
                                position INTEGER NOT NULL,
                                created_at DATETIME NOT NULL,
                                updated_at DATETIME DEFAULT NULL,
                                PRIMARY KEY (id))'
        );

        $this->addSql(
            'CREATE TABLE app_page_translation (
                                id BLOB NOT NULL,
                                title VARCHAR(150) NOT NULL,
                                slug VARCHAR(120) NOT NULL,
                                menu_title VARCHAR(150) DEFAULT NULL,
                                locale VARCHAR(5) NOT NULL,
                                created_at DATETIME NOT NULL,
                                updated_at DATETIME DEFAULT NULL,
                                page_id BLOB NOT NULL,
                                PRIMARY KEY (id),
                                CONSTRAINT FK_37EBFABCC4663E4 FOREIGN KEY (page_id) REFERENCES app_page (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE)'
        );

        $this->addSql('CREATE INDEX IDX_37EBFABCC4663E4 ON app_page_translation (page_id)');
        $this->addSql('CREATE UNIQUE INDEX uniq_page_translation ON app_page_translation (page_id, locale)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE app_page');
        $this->addSql('DROP TABLE app_page_translation');
    }
}
