<?php
/**
 * Copyright (c) Enalean, 2019. All Rights Reserved.
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

namespace Tuleap\DB;

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use ParagonIE\EasyDB\EasyDB;
use PHPUnit\Framework\TestCase;

final class DBConnectionTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    public function testOnlyOneDBIsCreated(): void
    {
        $db_creator    = \Mockery::mock(DBCreator::class);
        $db_connection = new DBConnection($db_creator);

        $db = \Mockery::mock(EasyDB::class);

        $db_creator->shouldReceive('createDB')->andReturn($db)->once();
        $this->assertSame($db, $db_connection->getDB());
        $this->assertSame($db, $db_connection->getDB());
    }

    public function testDBIsLazilyCreated(): void
    {
        $db_creator = \Mockery::mock(DBCreator::class);
        $db_creator->shouldNotReceive('createDB');

        $db_connection = new DBConnection($db_creator);
        $db_connection->reconnectAfterALongRunningProcess();
    }

    public function testExistingDBIsKeptIfConnectionHasNotBeenClosedAfterALongProcess(): void
    {
        $db_creator    = \Mockery::mock(DBCreator::class);
        $db_connection = new DBConnection($db_creator);

        $db = \Mockery::mock(EasyDB::class);
        $db_creator->shouldReceive('createDB')->andReturn($db)->once();
        $db->shouldReceive('run');

        $db_connection->reconnectAfterALongRunningProcess();
        $this->assertSame($db, $db_connection->getDB());
    }

    public function testNewDBIsCreatedIfTheConnectionHasBeenClosedAfterALongRunningProcess(): void
    {
        $db_creator    = \Mockery::mock(DBCreator::class);
        $db_connection = new DBConnection($db_creator);

        $db_closed  = \Mockery::mock(EasyDB::class);
        $db         = \Mockery::mock(EasyDB::class);
        $db_creator->shouldReceive('createDB')->andReturn($db_closed, $db);

        $db_closed->shouldReceive('run')->andReturnUsing(
            function (): void {
                trigger_error('MySQL server has gone away', E_USER_WARNING);
                throw new \PDOException('SQLSTATE[HY000]: General error: 2006 MySQL server has gone away');
            }
        );

        $db_connection->getDB();
        $db_connection->reconnectAfterALongRunningProcess();
        $this->assertSame($db, $db_connection->getDB());
    }

    public function testDBCommunicationFailureNotRelatedToAClosedStateAfterALongRunningProcessAreNotHidden(): void
    {
        $db_creator    = \Mockery::mock(DBCreator::class);
        $db_connection = new DBConnection($db_creator);

        $db = \Mockery::mock(EasyDB::class);
        $db->shouldReceive('run')->andThrow(\PDOException::class);
        $db_creator->shouldReceive('createDB')->andReturn($db);

        $db_connection->getDB();
        $this->expectException(\PDOException::class);
        $db_connection->reconnectAfterALongRunningProcess();
    }
}
