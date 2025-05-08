<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250427164519 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs

        // First, generate slugs for existing hotels
        $this->addSql(<<<'SQL'
            UPDATE hotel SET slug = CONCAT(LOWER(REPLACE(REPLACE(REPLACE(name, ' ', '-'), '.', ''), ',', '')), '-', id)
            WHERE slug = '' OR slug IS NULL
        SQL);

        // Then change the column definition and add the unique index
        $this->addSql(<<<'SQL'
            ALTER TABLE hotel CHANGE slug slug VARCHAR(255) NOT NULL
        SQL);
        $this->addSql(<<<'SQL'
            CREATE UNIQUE INDEX UNIQ_3535ED9989D9B62 ON hotel (slug)
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            DROP INDEX UNIQ_3535ED9989D9B62 ON hotel
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE hotel DROP slug
        SQL);
    }
}
