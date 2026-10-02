<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261002140300 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create the nurse credentials table for validated JSON imports';
    }

    public function up(Schema $schema): void
    {
        $table = $schema->createTable('nurse_credentials');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('nurse_user', 'string', ['length' => 64]);
        $table->addColumn('license_number', 'string', ['length' => 30]);
        $table->addColumn('certification', 'string', ['length' => 150]);
        $table->addColumn('issuing_body', 'string', ['length' => 150]);
        $table->addColumn('issue_date', 'date');
        $table->addColumn('expiration_date', 'date');
        $table->setPrimaryKey(['id']);
        $table->addUniqueIndex(
            ['nurse_user', 'license_number', 'certification'],
            'uniq_nurse_credential'
        );
    }

    public function down(Schema $schema): void
    {
        $schema->dropTable('nurse_credentials');
    }
}
