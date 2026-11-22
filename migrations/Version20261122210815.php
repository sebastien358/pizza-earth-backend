<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20261122210815 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE picture CHANGE product_id product_id INT DEFAULT NULL, CHANGE testimonial_id testimonial_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE picture ADD CONSTRAINT FK_16DB4F891D4EC6B1 FOREIGN KEY (testimonial_id) REFERENCES testimonial (id) ON DELETE CASCADE');
        $this->addSql('CREATE INDEX IDX_16DB4F891D4EC6B1 ON picture (testimonial_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE picture DROP FOREIGN KEY FK_16DB4F891D4EC6B1');
        $this->addSql('DROP INDEX IDX_16DB4F891D4EC6B1 ON picture');
        $this->addSql('ALTER TABLE picture CHANGE product_id product_id INT NOT NULL, CHANGE testimonial_id testimonial_id INT NOT NULL');
    }
}
