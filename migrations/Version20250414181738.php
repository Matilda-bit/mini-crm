<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250414181738 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        // $this->addSql(<<<'SQL'
        //     ALTER TABLE users DROP INDEX FK_1483A5E93414710B, ADD UNIQUE INDEX UNIQ_1483A5E93414710B (agent_id)
        // SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE users CHANGE currency currency ENUM('USD', 'EUR', 'GBP', 'BTC') NOT NULL, CHANGE role role ENUM('USER', 'ADMIN', 'REP') NOT NULL, CHANGE date_created date_created DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        // $this->addSql(<<<'SQL'
        //     ALTER TABLE users DROP INDEX UNIQ_1483A5E93414710B, ADD INDEX FK_1483A5E93414710B (agent_id)
        // SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE users CHANGE currency currency VARCHAR(255) NOT NULL, CHANGE role role VARCHAR(255) NOT NULL, CHANGE date_created date_created DATETIME NOT NULL
        SQL);
    }
}
