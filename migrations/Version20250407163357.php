<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250407163357 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {

    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            DROP TABLE messenger_messages
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE booking DROP FOREIGN KEY FK_E00CEDDEF6944204
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE booking DROP FOREIGN KEY FK_E00CEDDE64B64DCC
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE booking DROP FOREIGN KEY FK_E00CEDDEF6944204
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE booking ADD startDate DATE NOT NULL, ADD endDate DATE NOT NULL, DROP start_date, DROP end_date, CHANGE id id INT AUTO_INCREMENT NOT NULL, CHANGE userId userId INT NOT NULL, CHANGE hotelId hotelId INT NOT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE booking ADD CONSTRAINT booking_ibfk_1 FOREIGN KEY (hotelId) REFERENCES hotel (id)
        SQL);
        $this->addSql(<<<'SQL'
            DROP INDEX idx_e00ceddef6944204 ON booking
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX hotelId ON booking (hotelId)
        SQL);
        $this->addSql(<<<'SQL'
            DROP INDEX idx_e00cedde64b64dcc ON booking
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX fk_booking_user ON booking (userId)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE booking ADD CONSTRAINT FK_E00CEDDE64B64DCC FOREIGN KEY (userId) REFERENCES users (id) ON DELETE CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE booking ADD CONSTRAINT FK_E00CEDDEF6944204 FOREIGN KEY (hotelId) REFERENCES hotel (id) ON DELETE CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE hotel ADD ecoCertification TINYINT(1) DEFAULT NULL, DROP eco_certification, CHANGE id id INT AUTO_INCREMENT NOT NULL, CHANGE rating rating DOUBLE PRECISION DEFAULT NULL, CHANGE description description TEXT DEFAULT NULL, CHANGE price_per_night pricePerNight DOUBLE PRECISION NOT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE users CHANGE id id INT AUTO_INCREMENT NOT NULL
        SQL);
        $this->addSql(<<<'SQL'
            CREATE UNIQUE INDEX email ON users (email)
        SQL);
    }
}
