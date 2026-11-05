<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260623132819 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE app_contact_message (
                                id BLOB NOT NULL,
                                full_name VARCHAR(150) NOT NULL,
                                email VARCHAR(180) NOT NULL,
                                subject VARCHAR(200) NOT NULL,
                                message CLOB NOT NULL,
                                created_at DATETIME NOT NULL,
                                updated_at DATETIME DEFAULT NULL,
                                PRIMARY KEY (id)
                             )');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE app_contact_message');
    }
}
