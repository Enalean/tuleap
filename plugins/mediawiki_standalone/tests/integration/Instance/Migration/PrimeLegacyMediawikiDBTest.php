<?php
/**
 * Copyright (c) Enalean, 2023 - Present. All Rights Reserved.
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

namespace Tuleap\MediawikiStandalone\Instance\Migration;

use Psr\Log\NullLogger;
use Tuleap\DB\DBFactory;
use Tuleap\NeverThrow\Result;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;

final class PrimeLegacyMediawikiDBTest extends TestCase
{
    private const DB_PREFIX            = 'migration_';
    private const MW_USER_TABLE        = self::DB_PREFIX . 'user';
    private const MAPPING_TABLE        = self::DB_PREFIX . 'tuleap_user_mapping';
    private const TEST_USER_NAME       = 'mwuser1';
    private const ADDITIONAL_TEST_DB   = 'testdb_mw_standalone_preparation';
    private const TEST_TABLE_BASE_NAME = 'test_migration';

    protected function setUp(): void
    {
        $db = DBFactory::getMainTuleapDBConnection()->getDB();
        $db->run('INSERT INTO user(user_name) VALUES (?)', self::TEST_USER_NAME);
        $db->run(
            sprintf('CREATE TABLE %s (user_id int unsigned NOT NULL PRIMARY KEY AUTO_INCREMENT, user_name varchar(255) binary NOT NULL)', $db->escapeIdentifier(self::MW_USER_TABLE))
        );
        $db->run(
            sprintf('INSERT INTO %s (user_name) VALUES (?)', $db->escapeIdentifier(self::MW_USER_TABLE)),
            ucfirst(self::TEST_USER_NAME),
        );
    }

    protected function tearDown(): void
    {
        $db = DBFactory::getMainTuleapDBConnection()->getDB();
        $db->run('DELETE FROM plugin_mediawiki_database');
        $db->run('DELETE FROM user WHERE user_name = ?', self::TEST_USER_NAME);
        $db->run(sprintf('DROP TABLE %s', $db->escapeIdentifier(self::MW_USER_TABLE)));
        $db->run(sprintf('DROP TABLE IF EXISTS %s', $db->escapeIdentifier(self::MAPPING_TABLE)));
        try {
            $db->run(sprintf('DROP DATABASE %s', $db->escapeIdentifier(self::ADDITIONAL_TEST_DB)));
        } catch (\RuntimeException $exception) {
            // If we are not testing a migration with a move to a single DB this table might not exist
        }
        try {
            $db->run(sprintf('DROP TABLE %s', $db->escapeIdentifier(self::DB_PREFIX . self::TEST_TABLE_BASE_NAME)));
        } catch (\RuntimeException $exception) {
            // If we are not testing a migration with a move to a single DB this table might not exist
        }
    }

    public function testMappingTableCreation(): void
    {
        $legacy_mw_db_migration_primer = new PrimeLegacyMediawikiDB();

        $db_name    = \ForgeConfig::get('sys_dbname');
        $project_id = 102;

        $db = DBFactory::getMainTuleapDBConnection()->getDB();
        $db->run(
            'INSERT INTO plugin_mediawiki_database(project_id, database_name) VALUES (?, ?)',
            $project_id,
            $db_name,
        );

        $prepare_result = $legacy_mw_db_migration_primer->prepareDBForMigration(
            new NullLogger(),
            ProjectTestBuilder::aProject()->withId($project_id)->build(),
            $db_name,
            self::DB_PREFIX
        );

        self::assertTrue(Result::isOk($prepare_result));

        $mapped_users = $db->run(
            sprintf('SELECT tum_user_name AS user_name FROM %s', $db->escapeIdentifier(self::MAPPING_TABLE))
        );

        self::assertEquals([['user_name' => ucfirst(self::TEST_USER_NAME)]], $mapped_users);
    }

    public function testMappingTableCreationWithMigrationToSingleDB(): void
    {
        $legacy_mw_db_migration_primer = new PrimeLegacyMediawikiDB();

        $db_name    = \ForgeConfig::get('sys_dbname');
        $project_id = 102;

        $db = DBFactory::getMainTuleapDBConnection()->getDB();
        $db->run(
            'INSERT INTO plugin_mediawiki_database(project_id, database_name) VALUES (?, ?)',
            $project_id,
            self::ADDITIONAL_TEST_DB,
        );
        $db->run(sprintf('CREATE DATABASE %s', $db->escapeIdentifier(self::ADDITIONAL_TEST_DB)));
        $db->run(
            sprintf(
                'CREATE TABLE %s.%s (id INT)',
                $db->escapeIdentifier(self::ADDITIONAL_TEST_DB),
                $db->escapeIdentifier('mw' . self::TEST_TABLE_BASE_NAME)
            )
        );

        $prepare_result = $legacy_mw_db_migration_primer->prepareDBForMigration(
            new NullLogger(),
            ProjectTestBuilder::aProject()->withId($project_id)->build(),
            $db_name,
            self::DB_PREFIX
        );

        self::assertTrue(Result::isOk($prepare_result));

        $mapped_users = $db->run(
            sprintf('SELECT tum_user_name AS user_name FROM %s', $db->escapeIdentifier(self::MAPPING_TABLE))
        );

        self::assertEquals([['user_name' => ucfirst(self::TEST_USER_NAME)]], $mapped_users);

        self::assertNotEmpty(
            $db->run(
                'SELECT TABLE_SCHEMA, TABLE_NAME
                FROM information_schema.TABLES
                WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ?',
                $db_name,
                self::DB_PREFIX . self::TEST_TABLE_BASE_NAME
            )
        );
    }
}
