<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260708092000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(
            'CREATE TABLE app_task (
                                id BLOB NOT NULL,
                                position INTEGER NOT NULL,
                                created_at DATETIME NOT NULL,
                                updated_at DATETIME DEFAULT NULL,
                                PRIMARY KEY (id))'
        );
        $this->addSql(
            'CREATE TABLE app_task_translation (
                                id BLOB NOT NULL,
                                name VARCHAR(255) NOT NULL,
                                description CLOB DEFAULT NULL,
                                tools VARCHAR(255) NOT NULL,
                                impact CLOB NOT NULL,
                                locale VARCHAR(5) NOT NULL,
                                created_at DATETIME NOT NULL,
                                updated_at DATETIME DEFAULT NULL,
                                task_id BLOB NOT NULL,
                                PRIMARY KEY (id),
                                CONSTRAINT FK_662209FC8DB60186 FOREIGN KEY (task_id) REFERENCES app_task (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE)'
        );

        $this->addSql('CREATE INDEX IDX_662209FC8DB60186 ON app_task_translation (task_id)');
        $this->addSql('CREATE UNIQUE INDEX uniq_task_translation ON app_task_translation (task_id, locale)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE app_task');
        $this->addSql('DROP TABLE app_task_translation');
    }
}
