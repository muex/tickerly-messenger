<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Adds the final whistle: the moment the owner declared a game over.
 *
 * NULL means the game is still running, which is what every existing row is —
 * nothing was ever finishable before.
 */
final class Version20260830090000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Game.finished_at';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE game ADD finished_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE game DROP finished_at');
    }
}
