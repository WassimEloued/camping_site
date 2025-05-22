<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250510215100 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add created_at column to user table';
    }

    public function up(Schema $schema): void
    {
        // Check if the column doesn't exist before adding it
        if (!$schema->getTable('user')->hasColumn('created_at')) {
            $this->addSql('ALTER TABLE user ADD created_at DATETIME NOT NULL');
        }
    }

    public function down(Schema $schema): void
    {
        // Check if the column exists before dropping it
        if ($schema->getTable('user')->hasColumn('created_at')) {
            $this->addSql('ALTER TABLE user DROP created_at');
        }
    }
} 