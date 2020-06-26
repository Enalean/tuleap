<?php
/**
 * Copyright (c) Enalean, 2020-Present. All Rights Reserved.
 *
 * This file is a part of Tuleap.
 *
 * Tuleap is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Tuleap is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

declare(strict_types=1);

namespace Tuleap\DAO;

use PHPUnit\Framework\TestCase;
use Tuleap\DB\DBConnection;
use Tuleap\DB\DBCreator;
use Tuleap\DB\DBFactory;

abstract class DBTablesDaoLoadKnowDataTest extends TestCase
{
    private const DB_TEST_NAME = 'testdb_dbtablesloadknowdata';

    /**
     * @var DBConnection
     */
    private $db_connection;
    /**
     * @var DBTablesDao
     */
    private $db_tables_dao;

    protected function setUp(): void
    {
        $this->createDatabase(self::DB_TEST_NAME);

        $this->db_connection = new DBConnection(new DBCreator(self::DB_TEST_NAME));
        $this->db_tables_dao = new DBTablesDao($this->db_connection);
    }

    protected function tearDown(): void
    {
        DBFactory::getMainTuleapDBConnection()->getDB()->run('DROP DATABASE ' . self::DB_TEST_NAME);
    }

    abstract protected function createDatabase(string $db_name): void;

    public function testLoadCoreDatabaseStructureAndValues(): void
    {
        $this->db_tables_dao->updateFromFile(__DIR__ . '/../../../../src/db/mysql/database_structure.sql');
        $this->db_tables_dao->updateFromFile(__DIR__ . '/../../../../src/db/mysql/database_initvalues.sql');

        $this->assertNotEmpty($this->db_connection->getDB()->run('SHOW TABLES'));
    }

    public function testInstallThenUninstallPlugin(): void
    {
        $this->db_tables_dao->updateFromFile(__DIR__ . '/../../../../src/db/mysql/database_structure.sql');
        $tables_before_plugins = $this->retrieveTableNames();

        foreach (glob(__DIR__ . '/../../../../plugins/*/db') as $plugin_db_folder) {
            $install_sql_file = $plugin_db_folder . '/install.sql';
            if (file_exists($install_sql_file)) {
                $this->db_tables_dao->updateFromFile($install_sql_file);
            }
            $uninstall_sql_file = $plugin_db_folder . '/uninstall.sql';
            if (file_exists($uninstall_sql_file)) {
                $this->db_tables_dao->updateFromFile($uninstall_sql_file);
            }
        }

        $this->assertEquals([], array_diff($this->retrieveTableNames(), $tables_before_plugins));
    }

    private function retrieveTableNames(): array
    {
        $tables = $this->db_connection->getDB()->col(
            'SELECT table_name FROM information_schema.tables WHERE table_schema = ?',
            0,
            self::DB_TEST_NAME
        );
        return $tables;
    }
}
