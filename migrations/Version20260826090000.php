<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Makes the score columns non-nullable with a default of 0.
 *
 * A game always has a score — 0 : 0 before anyone has tapped anything — so
 * "no score yet" and "zero" were never two different things. Existing NULLs
 * are filled before the column is tightened.
 */
final class Version20260826090000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Score columns NOT NULL DEFAULT 0';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('UPDATE game SET homepoints = 0 WHERE homepoints IS NULL');
        $this->addSql('UPDATE game SET awaypoints = 0 WHERE awaypoints IS NULL');
        $this->addSql('ALTER TABLE game CHANGE homepoints homepoints INT DEFAULT 0 NOT NULL, CHANGE awaypoints awaypoints INT DEFAULT 0 NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // The zeros written above stay zeros; they are indistinguishable from
        // scores that were always 0, and nothing is lost by keeping them.
        $this->addSql('ALTER TABLE game CHANGE homepoints homepoints INT DEFAULT NULL, CHANGE awaypoints awaypoints INT DEFAULT NULL');
    }
}
