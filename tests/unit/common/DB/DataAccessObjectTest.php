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

final class DataAccessObjectTest extends \Tuleap\Test\PHPUnit\TestCase
{
    public function testDBIsAlwaysRetrievedFromTheConnection(): void
    {
        $connection = $this->createMock(DBConnection::class);

        $dao = new class ($connection) extends DataAccessObject {
            public function getDBPubliclyForTest(): EasyDB
            {
                return $this->getDB();
            }
        };

        $db1 = $this->createMock(EasyDB::class);
        $db2 = $this->createMock(EasyDB::class);
        $connection->method('getDB')->willReturn($db1, $db2);

        self::assertSame($db1, $dao->getDBPubliclyForTest());
        self::assertSame($db2, $dao->getDBPubliclyForTest());
    }

    public function testFoundRowsReturnsAnInteger(): void
    {
        $connection = $this->createMock(DBConnection::class);

        $dao = new class ($connection) extends DataAccessObject {
        };

        $db = $this->createMock(EasyDB::class);
        $connection->method('getDB')->willReturn($db);
        $db->method('single')->willReturn('0');

        self::assertSame(0, $dao->foundRows());
    }
}
