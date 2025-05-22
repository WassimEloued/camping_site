<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20240320000001 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add status column to event table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE event ADD status VARCHAR(20) NOT NULL DEFAULT \'pending\'');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE event DROP status');
    }
} 