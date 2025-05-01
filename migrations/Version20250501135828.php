<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250501135828 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            CREATE TABLE country (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(100) NOT NULL, phone_prefix VARCHAR(10) NOT NULL, phone_number_length INT NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE feedback (id INT AUTO_INCREMENT NOT NULL, rating INT NOT NULL, content LONGTEXT NOT NULL, date DATETIME NOT NULL, userId INT DEFAULT NULL, INDEX IDX_D229445864B64DCC (userId), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE feedback ADD CONSTRAINT FK_D229445864B64DCC FOREIGN KEY (userId) REFERENCES user (id) ON DELETE CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE user ADD first_name VARCHAR(255) DEFAULT NULL, ADD last_name VARCHAR(255) DEFAULT NULL, ADD gender VARCHAR(255) DEFAULT NULL, ADD date_of_birth DATE DEFAULT NULL, ADD phone_number VARCHAR(255) DEFAULT NULL, ADD country VARCHAR(255) DEFAULT NULL, ADD profile_picture_url VARCHAR(255) DEFAULT NULL, ADD updated_at DATETIME DEFAULT NULL, ADD is_banned TINYINT(1) NOT NULL, DROP full_name, CHANGE email email VARCHAR(255) DEFAULT NULL, CHANGE password password VARCHAR(1000) NOT NULL
        SQL);
        $this->addSql(<<<'SQL'
            DROP INDEX uniq_identifier_email ON user
        SQL);
        $this->addSql(<<<'SQL'
            CREATE UNIQUE INDEX UNIQ_8D93D649E7927C74 ON user (email)
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE feedback DROP FOREIGN KEY FK_D229445864B64DCC
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE country
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE feedback
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE user ADD full_name VARCHAR(255) NOT NULL, DROP first_name, DROP last_name, DROP gender, DROP date_of_birth, DROP phone_number, DROP country, DROP profile_picture_url, DROP updated_at, DROP is_banned, CHANGE email email VARCHAR(180) NOT NULL, CHANGE password password VARCHAR(255) NOT NULL
        SQL);
        $this->addSql(<<<'SQL'
            DROP INDEX uniq_8d93d649e7927c74 ON user
        SQL);
        $this->addSql(<<<'SQL'
            CREATE UNIQUE INDEX UNIQ_IDENTIFIER_EMAIL ON user (email)
        SQL);
    }
}
