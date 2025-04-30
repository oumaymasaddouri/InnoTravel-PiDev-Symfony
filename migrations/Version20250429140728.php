<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250429140728 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE reservation (id INT AUTO_INCREMENT NOT NULL, transport_id INT DEFAULT NULL, pickup_address VARCHAR(255) NOT NULL, destination_address VARCHAR(255) NOT NULL, price DOUBLE PRECISION DEFAULT NULL, status VARCHAR(255) NOT NULL, created_at DATETIME NOT NULL, estimated_duration INT DEFAULT NULL, sepcial_requests LONGTEXT DEFAULT NULL, pickup_latitude DOUBLE PRECISION DEFAULT NULL, pickup_longitude DOUBLE PRECISION DEFAULT NULL, INDEX IDX_42C849559909C13F (transport_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE transport (id INT AUTO_INCREMENT NOT NULL, vehicle_type VARCHAR(20) NOT NULL, car_model VARCHAR(100) NOT NULL, status VARCHAR(255) NOT NULL, car_color VARCHAR(50) NOT NULL, license_plate VARCHAR(50) NOT NULL, max_luggage INT DEFAULT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE messenger_messages (id BIGINT AUTO_INCREMENT NOT NULL, body LONGTEXT NOT NULL, headers LONGTEXT NOT NULL, queue_name VARCHAR(190) NOT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', available_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', delivered_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_75EA56E0FB7336F0 (queue_name), INDEX IDX_75EA56E0E3BD61CE (available_at), INDEX IDX_75EA56E016BA31DB (delivered_at), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE reservation ADD CONSTRAINT FK_42C849559909C13F FOREIGN KEY (transport_id) REFERENCES transport (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE reservation DROP FOREIGN KEY FK_42C849559909C13F');
        $this->addSql('DROP TABLE reservation');
        $this->addSql('DROP TABLE transport');
        $this->addSql('DROP TABLE messenger_messages');
    }
}
