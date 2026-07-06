<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260706192221 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Baseline schema: user, game, game_event';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE game (id INT AUTO_INCREMENT NOT NULL, owner_id INT NOT NULL, location VARCHAR(255) NOT NULL, home VARCHAR(255) NOT NULL, away VARCHAR(255) NOT NULL, datetime DATETIME NOT NULL, homepoints INT DEFAULT NULL, awaypoints INT DEFAULT NULL, INDEX IDX_232B318C7E3C61F9 (owner_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE game_event (id INT AUTO_INCREMENT NOT NULL, game_id INT DEFAULT NULL, timecode VARCHAR(10) DEFAULT NULL, message VARCHAR(255) DEFAULT NULL, INDEX IDX_99D7328E48FD905 (game_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE `user` (id INT AUTO_INCREMENT NOT NULL, email VARCHAR(180) NOT NULL, roles JSON NOT NULL COMMENT \'(DC2Type:json)\', password VARCHAR(255) NOT NULL, UNIQUE INDEX UNIQ_8D93D649E7927C74 (email), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE game ADD CONSTRAINT FK_232B318C7E3C61F9 FOREIGN KEY (owner_id) REFERENCES `user` (id)');
        $this->addSql('ALTER TABLE game_event ADD CONSTRAINT FK_99D7328E48FD905 FOREIGN KEY (game_id) REFERENCES game (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE game DROP FOREIGN KEY FK_232B318C7E3C61F9');
        $this->addSql('ALTER TABLE game_event DROP FOREIGN KEY FK_99D7328E48FD905');
        $this->addSql('DROP TABLE game');
        $this->addSql('DROP TABLE game_event');
        $this->addSql('DROP TABLE `user`');
    }
}
