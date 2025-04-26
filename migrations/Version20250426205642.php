<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250426205642 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE hotel_image DROP FOREIGN KEY FK_26E9CA9B3243BB18
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE hotel_image
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE hotel DROP updated_at, CHANGE image_name image VARCHAR(255) DEFAULT NULL
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            CREATE TABLE hotel_image (id INT AUTO_INCREMENT NOT NULL, hotel_id INT NOT NULL, image_name VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, updated_at DATETIME NOT NULL, caption VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, position INT DEFAULT NULL, INDEX IDX_26E9CA9B3243BB18 (hotel_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB COMMENT = '' 
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE hotel_image ADD CONSTRAINT FK_26E9CA9B3243BB18 FOREIGN KEY (hotel_id) REFERENCES hotel (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE hotel ADD updated_at DATETIME NOT NULL, CHANGE image image_name VARCHAR(255) DEFAULT NULL
        SQL);
    }
}
