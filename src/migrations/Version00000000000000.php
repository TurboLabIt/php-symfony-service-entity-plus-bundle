<?php declare(strict_types=1);
namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;
use Doctrine\Migrations\Exception\IrreversibleMigration;


final class Version00000000000000 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql('SET FOREIGN_KEY_CHECKS = 0');

        // Get a list of all tables
        $connection     = $this->connection;
        $databaseName   = $connection->fetchOne('SELECT DATABASE()');

        $tables =
            $connection->fetchFirstColumn("
                SELECT table_name
                FROM information_schema.tables
                WHERE table_schema = ?
            ", [$databaseName]
            );

        foreach($tables as $table) {

            if( $table == 'doctrine_migration_versions' ) {
                continue;
            }

            $this->addSql("DROP TABLE IF EXISTS `$table`");
        }

        $this->addSql('SET FOREIGN_KEY_CHECKS = 1');
    }


    public function down(Schema $schema): void
    {
        throw new IrreversibleMigration('Version00000000000000 is a cleanup migration and cannot be reverted.');
    }


    // prevent User Deprecated: Context: trying to commit a transaction Problem: the transaction is already committed, relying on silencing is deprecated
    // public function isTransactional(): bool { return false; }
}
