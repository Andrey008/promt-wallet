<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260310000001 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add prompt_compositions and snippets tables';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE prompt_compositions (
            id SERIAL PRIMARY KEY,
            composed_text TEXT NOT NULL,
            template_title VARCHAR(200) NOT NULL,
            project_name VARCHAR(100) DEFAULT NULL,
            context_titles JSON NOT NULL DEFAULT \'[]\',
            created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
            owner_id INT NOT NULL REFERENCES users(id) NOT DEFERRABLE INITIALLY IMMEDIATE
        )');
        $this->addSql('CREATE INDEX idx_composition_owner_date ON prompt_compositions (owner_id, created_at)');
        $this->addSql('COMMENT ON COLUMN prompt_compositions.created_at IS \'(DC2Type:datetime_immutable)\'');

        $this->addSql('CREATE TABLE snippets (
            id SERIAL PRIMARY KEY,
            title VARCHAR(200) NOT NULL,
            content TEXT NOT NULL,
            created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
            updated_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL,
            owner_id INT NOT NULL REFERENCES users(id) NOT DEFERRABLE INITIALLY IMMEDIATE
        )');
        $this->addSql('CREATE INDEX idx_snippet_owner ON snippets (owner_id)');
        $this->addSql('COMMENT ON COLUMN snippets.created_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('COMMENT ON COLUMN snippets.updated_at IS \'(DC2Type:datetime_immutable)\'');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE prompt_compositions');
        $this->addSql('DROP TABLE snippets');
    }
}
