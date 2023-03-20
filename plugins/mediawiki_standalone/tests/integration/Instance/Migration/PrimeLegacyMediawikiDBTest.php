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

use Tuleap\DB\DBFactory;
use Tuleap\Test\PHPUnit\TestCase;

final class PrimeLegacyMediawikiDBTest extends TestCase
{
    private const DB_PREFIX      = 'migration_';
    private const MW_USER_TABLE  = self::DB_PREFIX . 'user';
    private const MAPPING_TABLE  = self::DB_PREFIX . 'tuleap_user_mapping';
    private const TEST_USER_NAME = 'mwuser1';

    protected function setUp(): void
    {
        $db = DBFactory::getMainTuleapDBConnection()->getDB();
        $db->run('INSERT INTO user(user_name) VALUES (?)', self::TEST_USER_NAME);
        $db->run(
            sprintf('CREATE TABLE %s (user_name varchar(255) binary NOT NULL)', $db->escapeIdentifier(self::MW_USER_TABLE))
        );
        $db->run(
            sprintf('INSERT INTO %s (user_name) VALUES (?)', $db->escapeIdentifier(self::MW_USER_TABLE)),
            ucfirst(self::TEST_USER_NAME),
        );
    }

    protected function tearDown(): void
    {
        $db = DBFactory::getMainTuleapDBConnection()->getDB();
        $db->run('DELETE FROM user WHERE user_name = ?', self::TEST_USER_NAME);
        $db->run(sprintf('DROP TABLE %s', $db->escapeIdentifier(self::MW_USER_TABLE)));
        $db->run(sprintf('DROP TABLE %s', $db->escapeIdentifier(self::MAPPING_TABLE)));
    }

    public function testMappingTableCreation(): void
    {
        $legacy_mw_db_migration_primer = new PrimeLegacyMediawikiDB();

        $legacy_mw_db_migration_primer->prepareDBForMigration(\ForgeConfig::get('sys_dbname'), self::DB_PREFIX);

        $db           = DBFactory::getMainTuleapDBConnection()->getDB();
        $mapped_users = $db->run(
            sprintf('SELECT tum_user_name AS user_name FROM %s', $db->escapeIdentifier(self::MAPPING_TABLE))
        );

        self::assertEquals([['user_name' => ucfirst(self::TEST_USER_NAME)]], $mapped_users);
    }
}
