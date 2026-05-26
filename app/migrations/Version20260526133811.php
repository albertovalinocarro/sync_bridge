<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260526133811 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add idempotency_key field with unique constraint to webhook_event';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE webhook_event ADD idempotency_key VARCHAR(255) DEFAULT NULL');
        $this->addSql('CREATE UNIQUE INDEX uniq_idempotency_key ON webhook_event (idempotency_key)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP INDEX uniq_idempotency_key ON webhook_event');
        $this->addSql('ALTER TABLE webhook_event DROP idempotency_key');
    }
}
