<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250507114508 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE reservation ADD reservation_date DATETIME DEFAULT NULL, ADD reservation_time TIME DEFAULT NULL, CHANGE user_id user_id INT NOT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE transport DROP FOREIGN KEY FK_66AB212EC3423909
        SQL);
        $this->addSql(<<<'SQL'
            DROP INDEX IDX_66AB212EC3423909 ON transport
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE transport DROP driver_id
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE reservation DROP reservation_date, DROP reservation_time, CHANGE user_id user_id INT DEFAULT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE transport ADD driver_id INT DEFAULT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE transport ADD CONSTRAINT FK_66AB212EC3423909 FOREIGN KEY (driver_id) REFERENCES user (id)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_66AB212EC3423909 ON transport (driver_id)
        SQL);
    }
}
