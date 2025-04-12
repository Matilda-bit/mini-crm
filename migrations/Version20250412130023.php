<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250412130023 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        
        // $this->addSql(<<<'SQL'
        //     ALTER TABLE trades ADD CONSTRAINT FK_BFA1112588AC559E FOREIGN KEY (opened_by_agent_id) REFERENCES users (id)
        // SQL);
        // $this->addSql(<<<'SQL'
        //     CREATE INDEX IDX_BFA1112588AC559E ON trades (opened_by_agent_id)
        // SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE users CHANGE currency currency ENUM('USD', 'EUR', 'GBP', 'BTC') NOT NULL, CHANGE role role ENUM('USER', 'ADMIN', 'REP') NOT NULL
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        // $this->addSql(<<<'SQL'
        //     ALTER TABLE trades DROP FOREIGN KEY FK_BFA1112588AC559E
        // SQL);
        // $this->addSql(<<<'SQL'
        //     DROP INDEX IDX_BFA1112588AC559E ON trades
        // SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE users CHANGE currency currency VARCHAR(255) NOT NULL, CHANGE role role VARCHAR(255) NOT NULL
        SQL);

    }
}
