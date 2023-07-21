<?php
/**
 * Copyright (c) Enalean, 2019 - Present. All Rights Reserved.
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

use ParagonIE\EasyDB\EasyDB;

final class DBConnectionTest extends \Tuleap\Test\PHPUnit\TestCase
{
    public function testOnlyOneDBIsCreated(): void
    {
        $db_creator    = $this->createMock(DBCreator::class);
        $db_connection = new DBConnection($db_creator);

        $db = $this->createMock(EasyDB::class);

        $db_creator->expects(self::once())->method('createDB')->willReturn($db);
        self::assertSame($db, $db_connection->getDB());
        self::assertSame($db, $db_connection->getDB());
    }

    public function testDBIsLazilyCreated(): void
    {
        $db_creator = $this->createMock(DBCreator::class);
        $db_creator->expects(self::never())->method('createDB');

        $db_connection = new DBConnection($db_creator);
        $db_connection->reconnectAfterALongRunningProcess();
    }

    public function testExistingDBIsKeptIfConnectionHasNotBeenClosedAfterALongProcess(): void
    {
        $db_creator    = $this->createMock(DBCreator::class);
        $db_connection = new DBConnection($db_creator);

        $db = $this->createMock(EasyDB::class);
        $db_creator->expects(self::once())->method('createDB')->willReturn($db);
        $db->method('run');

        $db_connection->reconnectAfterALongRunningProcess();
        self::assertSame($db, $db_connection->getDB());
    }

    public function testNewDBIsCreatedIfTheConnectionHasBeenClosedAfterALongRunningProcess(): void
    {
        $db_creator    = $this->createMock(DBCreator::class);
        $db_connection = new DBConnection($db_creator);

        $db_closed = $this->createMock(EasyDB::class);
        $db        = $this->createMock(EasyDB::class);
        $db_creator->method('createDB')->willReturn($db_closed, $db);

        $db_closed->method('run')->willReturnCallback(
            function (): void {
                trigger_error('MySQL server has gone away', E_USER_WARNING);
                throw new \PDOException('SQLSTATE[HY000]: General error: 2006 MySQL server has gone away');
            }
        );

        $db_connection->getDB();
        $db_connection->reconnectAfterALongRunningProcess();
        self::assertSame($db, $db_connection->getDB());
    }

    public function testDBCommunicationFailureNotRelatedToAClosedStateAfterALongRunningProcessAreNotHidden(): void
    {
        $db_creator    = $this->createMock(DBCreator::class);
        $db_connection = new DBConnection($db_creator);

        $db = $this->createMock(EasyDB::class);
        $db->method('run')->willThrowException(new \PDOException());
        $db_creator->method('createDB')->willReturn($db);

        $db_connection->getDB();
        self::expectException(\PDOException::class);
        $db_connection->reconnectAfterALongRunningProcess();
    }
}
