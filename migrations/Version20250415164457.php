<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250415164457 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            CREATE TABLE assets (id INT AUTO_INCREMENT NOT NULL, bid NUMERIC(15, 2) NOT NULL, ask NUMERIC(15, 2) NOT NULL, lot_size INT DEFAULT 10 NOT NULL, date_update DATETIME NOT NULL, asset_name VARCHAR(10) DEFAULT 'BTC/USD' NOT NULL, UNIQUE INDEX UNIQ_79D17D8EBA47BBCE (asset_name), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE log (id INT AUTO_INCREMENT NOT NULL, action_name VARCHAR(255) NOT NULL, date_created DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL, user_id INT NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE trades (id INT AUTO_INCREMENT NOT NULL, user_id INT NOT NULL, opened_by_agent_id INT NOT NULL, trade_size NUMERIC(15, 2) DEFAULT NULL, lot_count INT DEFAULT NULL, pnl NUMERIC(15, 2) DEFAULT NULL, payout NUMERIC(15, 2) DEFAULT NULL, used_margin NUMERIC(15, 2) DEFAULT NULL, entry_rate NUMERIC(15, 2) DEFAULT NULL, close_rate NUMERIC(15, 2) DEFAULT NULL, date_created DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL, date_close DATETIME DEFAULT NULL, status VARCHAR(20) DEFAULT 'open', position VARCHAR(20) DEFAULT NULL, stop_loss NUMERIC(15, 2) DEFAULT NULL, take_profit NUMERIC(15, 2) DEFAULT NULL, INDEX IDX_BFA11125A76ED395 (user_id), INDEX IDX_BFA1112588AC559E (opened_by_agent_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE users (id INT AUTO_INCREMENT NOT NULL, agent_id INT DEFAULT NULL, username VARCHAR(50) NOT NULL, password VARCHAR(255) NOT NULL, login_time DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL, currency ENUM('USD', 'EUR', 'BTC') NOT NULL, role ENUM('USER', 'ADMIN', 'REP') NOT NULL, total_pnl NUMERIC(15, 2) NOT NULL, equity NUMERIC(15, 2) NOT NULL, date_created DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL, UNIQUE INDEX UNIQ_1483A5E9F85E0677 (username), INDEX IDX_1483A5E93414710B (agent_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE messenger_messages (id BIGINT AUTO_INCREMENT NOT NULL, body LONGTEXT NOT NULL, headers LONGTEXT NOT NULL, queue_name VARCHAR(190) NOT NULL, created_at DATETIME NOT NULL, available_at DATETIME NOT NULL, delivered_at DATETIME DEFAULT NULL, INDEX IDX_75EA56E0FB7336F0 (queue_name), INDEX IDX_75EA56E0E3BD61CE (available_at), INDEX IDX_75EA56E016BA31DB (delivered_at), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE trades ADD CONSTRAINT FK_BFA11125A76ED395 FOREIGN KEY (user_id) REFERENCES users (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE trades ADD CONSTRAINT FK_BFA1112588AC559E FOREIGN KEY (opened_by_agent_id) REFERENCES users (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE users ADD CONSTRAINT FK_1483A5E93414710B FOREIGN KEY (agent_id) REFERENCES users (id)
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE trades DROP FOREIGN KEY FK_BFA11125A76ED395
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE trades DROP FOREIGN KEY FK_BFA1112588AC559E
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE users DROP FOREIGN KEY FK_1483A5E93414710B
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE assets
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE log
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE trades
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE users
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE messenger_messages
        SQL);
    }
}
