<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;
use Doctrine\Migrations\Exception\IrreversibleMigration;
use Symfony\Component\String\Slugger\AsciiSlugger;
use Symfony\Component\Uid\Uuid;

/**
 * Replaces the auto-increment primary keys of user, game and game_event with
 * UUIDs, and gives every game the slug that identifies it in public URLs.
 *
 * Existing rows are carried over: each one gets a UUIDv7 and, for games, a slug
 * built from the teams and the kickoff date, before the foreign keys are
 * rewired and the integer columns are dropped.
 */
final class Version20260823130000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Switch user, game and game_event to UUID ids and add game.slug';
    }

    public function up(Schema $schema): void
    {
        // Read the current rows first: the statements queued below only run
        // after this method returns, so the integer ids are still in place.
        $userIds = $this->connection->fetchFirstColumn('SELECT id FROM `user`');
        $games = $this->connection->fetchAllAssociative('SELECT id, home, away, datetime FROM game');
        $eventIds = $this->connection->fetchFirstColumn('SELECT id FROM game_event');

        // --- new columns alongside the old ones ---------------------------
        $this->addSql('ALTER TABLE `user` ADD uuid BINARY(16) DEFAULT NULL');
        $this->addSql('ALTER TABLE game ADD uuid BINARY(16) DEFAULT NULL, ADD owner_uuid BINARY(16) DEFAULT NULL, ADD slug VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE game_event ADD uuid BINARY(16) DEFAULT NULL, ADD game_uuid BINARY(16) DEFAULT NULL');

        // --- one UUID per existing row ------------------------------------
        foreach ($userIds as $id) {
            $this->addSql('UPDATE `user` SET uuid = UNHEX(?) WHERE id = ?', [self::hex(), $id]);
        }

        foreach ($games as $game) {
            $this->addSql(
                'UPDATE game SET uuid = UNHEX(?), slug = ? WHERE id = ?',
                [self::hex(), $this->slugFor($game, $games), $game['id']],
            );
        }

        foreach ($eventIds as $id) {
            $this->addSql('UPDATE game_event SET uuid = UNHEX(?) WHERE id = ?', [self::hex(), $id]);
        }

        // --- carry the relations over to the new keys ---------------------
        $this->addSql('UPDATE game g JOIN `user` u ON g.owner_id = u.id SET g.owner_uuid = u.uuid');
        $this->addSql('UPDATE game_event e JOIN game g ON e.game_id = g.id SET e.game_uuid = g.uuid');

        // --- drop the integer keys ----------------------------------------
        $this->addSql('ALTER TABLE game DROP FOREIGN KEY FK_232B318C7E3C61F9');
        $this->addSql('ALTER TABLE game_event DROP FOREIGN KEY FK_99D7328E48FD905');
        $this->addSql('DROP INDEX IDX_232B318C7E3C61F9 ON game');
        $this->addSql('DROP INDEX IDX_99D7328E48FD905 ON game_event');

        // AUTO_INCREMENT has to go before the primary key it hangs on.
        $this->addSql('ALTER TABLE `user` MODIFY id INT NOT NULL');
        $this->addSql('ALTER TABLE `user` DROP PRIMARY KEY');
        $this->addSql('ALTER TABLE `user` DROP id');
        $this->addSql("ALTER TABLE `user` CHANGE uuid id BINARY(16) NOT NULL COMMENT '(DC2Type:uuid)'");
        $this->addSql('ALTER TABLE `user` ADD PRIMARY KEY (id)');

        $this->addSql('ALTER TABLE game MODIFY id INT NOT NULL');
        $this->addSql('ALTER TABLE game DROP PRIMARY KEY');
        $this->addSql('ALTER TABLE game DROP id, DROP owner_id');
        $this->addSql("ALTER TABLE game CHANGE uuid id BINARY(16) NOT NULL COMMENT '(DC2Type:uuid)', CHANGE owner_uuid owner_id BINARY(16) NOT NULL COMMENT '(DC2Type:uuid)', CHANGE slug slug VARCHAR(255) NOT NULL");
        $this->addSql('ALTER TABLE game ADD PRIMARY KEY (id)');

        $this->addSql('ALTER TABLE game_event MODIFY id INT NOT NULL');
        $this->addSql('ALTER TABLE game_event DROP PRIMARY KEY');
        $this->addSql('ALTER TABLE game_event DROP id, DROP game_id');
        $this->addSql("ALTER TABLE game_event CHANGE uuid id BINARY(16) NOT NULL COMMENT '(DC2Type:uuid)', CHANGE game_uuid game_id BINARY(16) DEFAULT NULL COMMENT '(DC2Type:uuid)'");
        $this->addSql('ALTER TABLE game_event ADD PRIMARY KEY (id)');

        // --- indexes and foreign keys back on the UUID columns ------------
        $this->addSql('CREATE UNIQUE INDEX UNIQ_232B318C989D9B62 ON game (slug)');
        $this->addSql('CREATE INDEX IDX_232B318C7E3C61F9 ON game (owner_id)');
        $this->addSql('CREATE INDEX IDX_99D7328E48FD905 ON game_event (game_id)');
        $this->addSql('ALTER TABLE game ADD CONSTRAINT FK_232B318C7E3C61F9 FOREIGN KEY (owner_id) REFERENCES `user` (id)');
        $this->addSql('ALTER TABLE game_event ADD CONSTRAINT FK_99D7328E48FD905 FOREIGN KEY (game_id) REFERENCES game (id)');
    }

    public function down(Schema $schema): void
    {
        // The integer ids are gone for good once this migration has run, and
        // the slugs in shared links cannot be rebuilt from them either.
        throw new IrreversibleMigration('Switching back from UUIDs to integer ids would lose the identifiers.');
    }

    private static function hex(): string
    {
        return bin2hex(Uuid::v7()->toBinary());
    }

    /**
     * Mirrors App\Game\Domain\GameSlugger for the rows that already exist:
     * teams plus kickoff date, with a counter when the same fixture repeats
     * on the same day.
     *
     * @param array<string, mixed>            $game
     * @param array<int, array<string, mixed>> $allGames
     */
    private function slugFor(array $game, array $allGames): string
    {
        $slugger = new AsciiSlugger();
        $base = static fn (array $g): string => $slugger
            ->slug(sprintf('%s vs %s %s', $g['home'], $g['away'], substr((string) $g['datetime'], 0, 10)), '-', 'de')
            ->lower()
            ->toString();

        $slug = $base($game);
        $taken = 0;

        foreach ($allGames as $other) {
            if ($other['id'] === $game['id']) {
                break;
            }

            if ($base($other) === $slug) {
                ++$taken;
            }
        }

        return $taken === 0 ? $slug : $slug . '-' . ($taken + 1);
    }
}
