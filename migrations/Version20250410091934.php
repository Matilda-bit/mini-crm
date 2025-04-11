<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250410091934 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // Сначала удаляем внешний ключ, используя правильное имя
        $this->addSql('ALTER TABLE users DROP FOREIGN KEY FK_1483A5E93414710B');
        
        // Затем удаляем индекс
        $this->addSql('ALTER TABLE users DROP INDEX fk_agent_user');
        
        // Теперь добавляем новый индекс
        $this->addSql('ALTER TABLE users ADD INDEX fk_agent_user (agent_id)');
        
        // Изменяем колонки для валюты и роли
        $this->addSql('ALTER TABLE users CHANGE currency currency ENUM("USD", "EUR", "GBP", "BTC") NOT NULL, CHANGE role role ENUM("USER", "ADMIN", "REP") NOT NULL');
        
        // Добавляем новый внешний ключ
        $this->addSql('ALTER TABLE users ADD CONSTRAINT FK_1483A5E93414710B FOREIGN KEY (agent_id) REFERENCES users (id)');
    }

    public function down(Schema $schema): void
    {
        // Revert the changes: drop the new foreign key first
        $this->addSql('ALTER TABLE users DROP FOREIGN KEY FK_1483A5E93414710B');
        
        // Drop the new index
        $this->addSql('ALTER TABLE users DROP INDEX fk_agent_user');
        
        // Revert the column changes
        $this->addSql('ALTER TABLE users CHANGE currency currency VARCHAR(255) NOT NULL, CHANGE role role VARCHAR(255) DEFAULT NULL');
        
        // Re-add the original index and foreign key constraint
        $this->addSql('ALTER TABLE users ADD CONSTRAINT fk_agent_user FOREIGN KEY (agent_id) REFERENCES users (id) ON UPDATE NO ACTION ON DELETE SET NULL');
        $this->addSql('ALTER TABLE users ADD INDEX fk_agent_user (agent_id)');
    }
}