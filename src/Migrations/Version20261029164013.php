<?php

declare(strict_types=1);

namespace App\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20261029164013 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE asset (id INT AUTO_INCREMENT NOT NULL, asset_id VARCHAR(50) NOT NULL, user_name VARCHAR(150) NOT NULL, pc_name VARCHAR(100) NOT NULL, serial_number VARCHAR(150) NOT NULL, model VARCHAR(150) NOT NULL, department VARCHAR(100) NOT NULL, purchase_date DATE DEFAULT NULL, warranty_expiry DATE DEFAULT NULL, office_location VARCHAR(150) NOT NULL, status VARCHAR(20) DEFAULT \'Active\' NOT NULL, UNIQUE INDEX uniq_asset_asset_id (asset_id), UNIQUE INDEX uniq_asset_pc_name (pc_name), UNIQUE INDEX uniq_asset_serial_number (serial_number), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE messenger_messages (id BIGINT AUTO_INCREMENT NOT NULL, body LONGTEXT NOT NULL, headers LONGTEXT NOT NULL, queue_name VARCHAR(190) NOT NULL, created_at DATETIME NOT NULL, available_at DATETIME NOT NULL, delivered_at DATETIME DEFAULT NULL, INDEX IDX_75EA56E0FB7336F0E3BD61CE16BA31DBBF396750 (queue_name, available_at, delivered_at, id), PRIMARY KEY (id))');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE asset');
        $this->addSql('DROP TABLE messenger_messages');
    }
}
