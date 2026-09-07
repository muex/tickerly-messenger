<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Gives a game its sport and a ticker entry its event type.
 *
 * Everything that exists becomes "offen", which is what it has been all along:
 * a scoreboard with entries someone typed.
 */
final class Version20260907090000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Game.sport and GameEvent.type';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("ALTER TABLE game ADD sport VARCHAR(32) DEFAULT 'offen' NOT NULL");
        $this->addSql('ALTER TABLE game_event ADD type VARCHAR(64) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE game DROP sport');
        $this->addSql('ALTER TABLE game_event DROP type');
    }
}
