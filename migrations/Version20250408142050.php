<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250408142050 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE users CHANGE login_time login_time DATETIME DEFAULT NULL, CHANGE currency currency ENUM('USD', 'EUR', 'GBP', 'BTC') NOT NULL, CHANGE role role ENUM('USER', 'ADMIN') NOT NULL
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE users CHANGE login_time login_time DATETIME NOT NULL, CHANGE currency currency VARCHAR(255) NOT NULL, CHANGE role role VARCHAR(255) NOT NULL
        SQL);
    }
}
