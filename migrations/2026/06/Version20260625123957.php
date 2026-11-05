<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260625123957 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(
            'CREATE TABLE app_admin_user (
                                id BLOB NOT NULL,
                                email VARCHAR(180) NOT NULL,
                                username VARCHAR(60) NOT NULL,
                                roles CLOB NOT NULL,
                                password VARCHAR(255) NOT NULL,
                                enabled BOOLEAN DEFAULT 0 NOT NULL,
                                created_at DATETIME NOT NULL,
                                updated_at DATETIME DEFAULT NULL,
                                deleted_at DATETIME DEFAULT NULL,
                                PRIMARY KEY (id))'
        );

        $this->addSql('CREATE UNIQUE INDEX UNIQ_IDENTIFIER_EMAIL ON app_admin_user (email)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_IDENTIFIER_USERNAME ON app_admin_user (username)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE app_admin_user');
    }
}
