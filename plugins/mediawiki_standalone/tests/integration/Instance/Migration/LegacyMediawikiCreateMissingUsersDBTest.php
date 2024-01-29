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

use ParagonIE\EasyDB\EasyDB;
use Project;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Tuleap\DB\DBFactory;
use Tuleap\NeverThrow\Err;
use Tuleap\NeverThrow\Fault;
use Tuleap\NeverThrow\Ok;
use Tuleap\NeverThrow\Result;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;

final class LegacyMediawikiCreateMissingUsersDBTest extends TestCase
{
    private const DB_PREFIX         = 'migration_';
    private const MW_USER_TABLE     = self::DB_PREFIX . 'user';
    private const MW_REVISION_TABLE = self::DB_PREFIX . 'revision';
    private const TEST_USER_NAME    = 'mwuser1';

    protected function setUp(): void
    {
        $db = DBFactory::getMainTuleapDBConnection()->getDB();
        $db->run(
            sprintf('CREATE TABLE %s (user_id int unsigned NOT NULL PRIMARY KEY AUTO_INCREMENT, user_name varchar(255) binary NOT NULL)', $db->escapeIdentifier(self::MW_USER_TABLE))
        );
        $db->run(
            sprintf('CREATE TABLE %s (rev_id int unsigned NOT NULL PRIMARY KEY AUTO_INCREMENT, rev_user int unsigned NOT NULL default 0, rev_user_text varchar(255) binary NOT NULL default \'\')', $db->escapeIdentifier(self::MW_REVISION_TABLE))
        );
        $db->run(
            sprintf('INSERT INTO %s (user_name) VALUES (?)', $db->escapeIdentifier(self::MW_USER_TABLE)),
            ucfirst(self::TEST_USER_NAME),
        );
        parent::setUp();
    }

    protected function tearDown(): void
    {
        $db = DBFactory::getMainTuleapDBConnection()->getDB();
        parent::tearDown();
        $db->run('DELETE FROM plugin_mediawiki_database');
        $db->run(sprintf('DROP TABLE %s', $db->escapeIdentifier(self::MW_USER_TABLE)));
        $db->run(sprintf('DROP TABLE %s', $db->escapeIdentifier(self::MW_REVISION_TABLE)));
    }

    public function testRecreateUsersMissingFromRevision(): void
    {
        $db                            = DBFactory::getMainTuleapDBConnection()->getDB();
        $legacy_mw_db_migration_primer = new LegacyMediawikiCreateMissingUsersDB($this->getCreateMissingUser($db));

        $db_name    = \ForgeConfig::get('sys_dbname');
        $project_id = 102;

        $db = DBFactory::getMainTuleapDBConnection()->getDB();
        $db->run(
            'INSERT INTO plugin_mediawiki_database(project_id, database_name) VALUES (?, ?)',
            $project_id,
            $db_name,
        );

        $db->run(sprintf('INSERT INTO %s (rev_id, rev_user, rev_user_text) VALUES (1, 0, \'Jdoe\'), (2, 1, \'%s\'), (3, 0, \'Jdoe\'), (4, 0, \'Agathe\'), (5, 0, \'Jdoe\')', $db->escapeIdentifier(self::MW_REVISION_TABLE), ucfirst(self::TEST_USER_NAME)));

        $prepare_result = $legacy_mw_db_migration_primer->create(
            new NullLogger(),
            ProjectTestBuilder::aProject()->withId($project_id)->build(),
            self::DB_PREFIX,
        );
        self::assertTrue(Result::isOk($prepare_result));

        $result_mw_users = $db->run(sprintf('SELECT user_id, user_name FROM %s WHERE user_id != 0', $db->escapeIdentifier(self::MW_USER_TABLE)));
        $all_user_names  = [];
        $jdoe_id         = null;
        $agathe_id       = null;
        foreach ($result_mw_users as $user_row) {
            $all_user_names[] = $user_row['user_name'];
            if ($user_row['user_name'] === 'Jdoe') {
                $jdoe_id = $user_row['user_id'];
            }
            if ($user_row['user_name'] === 'Agathe') {
                $agathe_id = $user_row['user_id'];
            }
        }
        self::assertEquals([ucfirst(self::TEST_USER_NAME), 'Jdoe', 'Agathe'], $all_user_names);

        $result_mw_revision = $db->run(sprintf('SELECT rev_id, rev_user, rev_user_text FROM %s', $db->escapeIdentifier(self::MW_REVISION_TABLE)));
        self::assertEquals(
            [
                [
                    'rev_id' => 1,
                    'rev_user' => $jdoe_id,
                    'rev_user_text' => 'Jdoe',
                ],
                [
                    'rev_id' => 2,
                    'rev_user' => 1,
                    'rev_user_text' => ucfirst(self::TEST_USER_NAME),
                ],
                [
                    'rev_id' => 3,
                    'rev_user' => $jdoe_id,
                    'rev_user_text' => 'Jdoe',
                ],
                [
                    'rev_id' => 4,
                    'rev_user' => $agathe_id,
                    'rev_user_text' => 'Agathe',
                ],
                [
                    'rev_id' => 5,
                    'rev_user' => $jdoe_id,
                    'rev_user_text' => 'Jdoe',
                ],
            ],
            $result_mw_revision,
        );
    }

    public function testRecreateUsersWithFailureAtUserCreation(): void
    {
        $db                            = DBFactory::getMainTuleapDBConnection()->getDB();
        $legacy_mw_db_migration_primer = new LegacyMediawikiCreateMissingUsersDB($this->getCreateMissingUserError());

        $db_name    = \ForgeConfig::get('sys_dbname');
        $project_id = 102;

        $db->run(sprintf('INSERT INTO %s (rev_id, rev_user, rev_user_text) VALUES (1, 0, \'Jdoe\'), (2, 1, \'%s\'), (3, 0, \'Jdoe\'), (4, 0, \'Agathe\'), (5, 0, \'Jdoe\')', $db->escapeIdentifier(self::MW_REVISION_TABLE), ucfirst(self::TEST_USER_NAME)));

        $db->run(
            'INSERT INTO plugin_mediawiki_database(project_id, database_name) VALUES (?, ?)',
            $project_id,
            $db_name,
        );

        $prepare_result = $legacy_mw_db_migration_primer->create(
            new NullLogger(),
            ProjectTestBuilder::aProject()->withId($project_id)->build(),
            self::DB_PREFIX,
        );
        self::assertTrue(Result::isErr($prepare_result));
        self::assertInstanceOf(Fault::class, $prepare_result->error);
        self::assertEquals($prepare_result->error, 'Foo');
    }

    private function getCreateMissingUser(EasyDB $db): LegacyMediawikiCreateAndPromoteUser
    {
        return new class ($db, self::MW_USER_TABLE) implements LegacyMediawikiCreateAndPromoteUser {
            public function __construct(private EasyDB $db, private string $mw_user_table)
            {
            }

            public function create(LoggerInterface $logger, Project $project, string $rev_user_text): Ok|Err
            {
                $this->db->run(
                    sprintf('INSERT INTO %s (user_name) VALUES (?)', $this->db->escapeIdentifier($this->mw_user_table)),
                    ucfirst($rev_user_text),
                );
                return Result::ok(null);
            }
        };
    }

    private function getCreateMissingUserError(): LegacyMediawikiCreateAndPromoteUser
    {
        return new class implements LegacyMediawikiCreateAndPromoteUser {
            public function create(LoggerInterface $logger, Project $project, string $rev_user_text): Ok|Err
            {
                return Result::err(Fault::fromMessage('Foo'));
            }
        };
    }
}
