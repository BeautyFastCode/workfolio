<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260702094546 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(
            'CREATE TABLE app_contact_link (
                                id BLOB NOT NULL,
                                name VARCHAR(255) NOT NULL,
                                href VARCHAR(255) NOT NULL,
                                enabled BOOLEAN DEFAULT 0 NOT NULL,
                                position INTEGER NOT NULL,
                                icon VARCHAR(64) DEFAULT NULL,
                                created_at DATETIME NOT NULL,
                                updated_at DATETIME DEFAULT NULL,
                                PRIMARY KEY (id))'
        );
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE app_contact_link');
    }
}
