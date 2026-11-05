<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260630113924 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE app_cta_message (
                                id BLOB NOT NULL,
                                created_at DATETIME NOT NULL,
                                updated_at DATETIME DEFAULT NULL,
                                PRIMARY KEY (id))
                        ');
        $this->addSql('CREATE TABLE app_cta_message_translation (
                                id BLOB NOT NULL,
                                message VARCHAR(255) NOT NULL,
                                locale VARCHAR(5) NOT NULL,
                                created_at DATETIME NOT NULL,
                                updated_at DATETIME DEFAULT NULL,
                                cta_message_id BLOB NOT NULL,
                                PRIMARY KEY (id),
                                CONSTRAINT FK_FF564221B30BB7FD FOREIGN KEY (cta_message_id) REFERENCES app_cta_message (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE)
                            ');

        $this->addSql('CREATE INDEX IDX_FF564221B30BB7FD ON app_cta_message_translation (cta_message_id)');
        $this->addSql('CREATE UNIQUE INDEX uniq_cta_message_translation ON app_cta_message_translation (cta_message_id, locale)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE app_cta_message');
        $this->addSql('DROP TABLE app_cta_message_translation');
    }
}
