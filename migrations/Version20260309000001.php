<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260309000001 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add users table, reset_password_request table, and owner_id to projects, contexts, prompt_templates';
    }

    public function up(Schema $schema): void
    {
        // Create users table
        $this->addSql('CREATE TABLE users (
            id SERIAL PRIMARY KEY,
            email VARCHAR(180) NOT NULL,
            roles JSON NOT NULL DEFAULT \'["ROLE_USER"]\',
            password VARCHAR(255) NOT NULL,
            display_name VARCHAR(100) NOT NULL,
            is_verified BOOLEAN NOT NULL DEFAULT FALSE,
            created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL
        )');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_1483A5E9E7927C74 ON users (email)');
        $this->addSql('COMMENT ON COLUMN users.created_at IS \'(DC2Type:datetime_immutable)\'');

        // Create reset_password_request table
        $this->addSql('CREATE TABLE reset_password_request (
            id SERIAL PRIMARY KEY,
            user_id INT NOT NULL REFERENCES users(id) ON DELETE CASCADE,
            selector VARCHAR(20) NOT NULL,
            hashed_token VARCHAR(100) NOT NULL,
            requested_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
            expires_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL
        )');
        $this->addSql('CREATE INDEX IDX_RESET_PWD_USER ON reset_password_request (user_id)');
        $this->addSql('COMMENT ON COLUMN reset_password_request.requested_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('COMMENT ON COLUMN reset_password_request.expires_at IS \'(DC2Type:datetime_immutable)\'');

        // Add owner_id columns as nullable first
        $this->addSql('ALTER TABLE projects ADD owner_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE contexts ADD owner_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE prompt_templates ADD owner_id INT DEFAULT NULL');

        // Create a default admin user with password 'admin123' (should be changed after first login)
        $this->addSql("INSERT INTO users (email, roles, password, display_name, is_verified, created_at) VALUES ('admin@prompt-wallet.local', '[\"ROLE_USER\", \"ROLE_ADMIN\"]', '\$2y\$13\$x6T2tDAw8TI/XTb/x23MnuD2TWcTSxdVoCkcSOzN0kwqkZ6YQW9iS', 'Admin', true, NOW())");

        // Assign all existing data to the admin user
        $this->addSql('UPDATE projects SET owner_id = (SELECT id FROM users WHERE email = \'admin@prompt-wallet.local\')');
        $this->addSql('UPDATE contexts SET owner_id = (SELECT id FROM users WHERE email = \'admin@prompt-wallet.local\')');
        $this->addSql('UPDATE prompt_templates SET owner_id = (SELECT id FROM users WHERE email = \'admin@prompt-wallet.local\')');

        // Now set NOT NULL constraint
        $this->addSql('ALTER TABLE projects ALTER COLUMN owner_id SET NOT NULL');
        $this->addSql('ALTER TABLE contexts ALTER COLUMN owner_id SET NOT NULL');
        $this->addSql('ALTER TABLE prompt_templates ALTER COLUMN owner_id SET NOT NULL');

        // Add foreign key constraints
        $this->addSql('ALTER TABLE projects ADD CONSTRAINT FK_PROJECTS_OWNER FOREIGN KEY (owner_id) REFERENCES users(id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE contexts ADD CONSTRAINT FK_CONTEXTS_OWNER FOREIGN KEY (owner_id) REFERENCES users(id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE prompt_templates ADD CONSTRAINT FK_TEMPLATES_OWNER FOREIGN KEY (owner_id) REFERENCES users(id) NOT DEFERRABLE INITIALLY IMMEDIATE');

        // Add indexes
        $this->addSql('CREATE INDEX IDX_PROJECTS_OWNER ON projects (owner_id)');
        $this->addSql('CREATE INDEX IDX_CONTEXTS_OWNER ON contexts (owner_id)');
        $this->addSql('CREATE INDEX IDX_TEMPLATES_OWNER ON prompt_templates (owner_id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE reset_password_request');

        $this->addSql('ALTER TABLE projects DROP CONSTRAINT FK_PROJECTS_OWNER');
        $this->addSql('ALTER TABLE contexts DROP CONSTRAINT FK_CONTEXTS_OWNER');
        $this->addSql('ALTER TABLE prompt_templates DROP CONSTRAINT FK_TEMPLATES_OWNER');

        $this->addSql('ALTER TABLE projects DROP COLUMN owner_id');
        $this->addSql('ALTER TABLE contexts DROP COLUMN owner_id');
        $this->addSql('ALTER TABLE prompt_templates DROP COLUMN owner_id');

        $this->addSql('DROP TABLE users');
    }
}
