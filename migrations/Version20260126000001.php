<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Initial migration: Create all tables for Prompt Wallet
 */
final class Version20260126000001 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create initial database schema for Prompt Wallet';
    }

    public function up(Schema $schema): void
    {
        // Tags table
        $this->addSql('CREATE TABLE tags (
            id SERIAL PRIMARY KEY,
            name VARCHAR(50) NOT NULL UNIQUE,
            color VARCHAR(7) DEFAULT NULL,
            created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL
        )');
        $this->addSql('CREATE INDEX idx_tag_name ON tags (name)');
        $this->addSql('COMMENT ON COLUMN tags.created_at IS \'(DC2Type:datetime_immutable)\'');

        // Projects table
        $this->addSql('CREATE TABLE projects (
            id SERIAL PRIMARY KEY,
            name VARCHAR(100) NOT NULL,
            description TEXT DEFAULT NULL,
            stack JSON DEFAULT NULL,
            created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
            updated_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL
        )');
        $this->addSql('COMMENT ON COLUMN projects.created_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('COMMENT ON COLUMN projects.updated_at IS \'(DC2Type:datetime_immutable)\'');

        // Contexts table
        $this->addSql('CREATE TABLE contexts (
            id SERIAL PRIMARY KEY,
            project_id INT DEFAULT NULL,
            title VARCHAR(200) NOT NULL,
            content TEXT NOT NULL,
            scope VARCHAR(20) NOT NULL,
            sort_order INT NOT NULL DEFAULT 0,
            created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
            updated_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL,
            CONSTRAINT fk_context_project FOREIGN KEY (project_id) REFERENCES projects (id) ON DELETE SET NULL
        )');
        $this->addSql('CREATE INDEX idx_context_scope ON contexts (scope)');
        $this->addSql('CREATE INDEX idx_context_project ON contexts (project_id)');
        $this->addSql('COMMENT ON COLUMN contexts.created_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('COMMENT ON COLUMN contexts.updated_at IS \'(DC2Type:datetime_immutable)\'');

        // Prompt templates table
        $this->addSql('CREATE TABLE prompt_templates (
            id SERIAL PRIMARY KEY,
            title VARCHAR(200) NOT NULL,
            body TEXT NOT NULL,
            target_model VARCHAR(20) NOT NULL,
            description TEXT DEFAULT NULL,
            created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
            updated_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL
        )');
        $this->addSql('COMMENT ON COLUMN prompt_templates.created_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('COMMENT ON COLUMN prompt_templates.updated_at IS \'(DC2Type:datetime_immutable)\'');

        // Context-Tags junction table
        $this->addSql('CREATE TABLE context_tags (
            context_id INT NOT NULL,
            tag_id INT NOT NULL,
            PRIMARY KEY (context_id, tag_id),
            CONSTRAINT fk_context_tags_context FOREIGN KEY (context_id) REFERENCES contexts (id) ON DELETE CASCADE,
            CONSTRAINT fk_context_tags_tag FOREIGN KEY (tag_id) REFERENCES tags (id) ON DELETE CASCADE
        )');
        $this->addSql('CREATE INDEX idx_context_tags_context ON context_tags (context_id)');
        $this->addSql('CREATE INDEX idx_context_tags_tag ON context_tags (tag_id)');

        // PromptTemplate-Tags junction table
        $this->addSql('CREATE TABLE prompt_template_tags (
            prompt_template_id INT NOT NULL,
            tag_id INT NOT NULL,
            PRIMARY KEY (prompt_template_id, tag_id),
            CONSTRAINT fk_pt_tags_template FOREIGN KEY (prompt_template_id) REFERENCES prompt_templates (id) ON DELETE CASCADE,
            CONSTRAINT fk_pt_tags_tag FOREIGN KEY (tag_id) REFERENCES tags (id) ON DELETE CASCADE
        )');
        $this->addSql('CREATE INDEX idx_pt_tags_template ON prompt_template_tags (prompt_template_id)');
        $this->addSql('CREATE INDEX idx_pt_tags_tag ON prompt_template_tags (tag_id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE prompt_template_tags');
        $this->addSql('DROP TABLE context_tags');
        $this->addSql('DROP TABLE prompt_templates');
        $this->addSql('DROP TABLE contexts');
        $this->addSql('DROP TABLE projects');
        $this->addSql('DROP TABLE tags');
    }
}
