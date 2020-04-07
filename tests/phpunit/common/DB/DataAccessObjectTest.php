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

final class DataAccessObjectTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    public function testDBIsAlwaysRetrievedFromTheConnection(): void
    {
        $connection = \Mockery::mock(DBConnection::class);

        $dao = new class ($connection) extends DataAccessObject
        {
            public function getDBPubliclyForTest(): EasyDB
            {
                return $this->getDB();
            }
        };

        $db1 = \Mockery::mock(EasyDB::class);
        $db2 = \Mockery::mock(EasyDB::class);
        $connection->shouldReceive('getDB')->andReturn($db1, $db2);

        $this->assertSame($db1, $dao->getDBPubliclyForTest());
        $this->assertSame($db2, $dao->getDBPubliclyForTest());
    }

    public function testFoundRowsReturnsAnInteger(): void
    {
        $connection = \Mockery::mock(DBConnection::class);

        $dao = new class ($connection) extends DataAccessObject {
        };

        $db = \Mockery::mock(EasyDB::class);
        $connection->shouldReceive('getDB')->andReturn($db);
        $db->shouldReceive('single')->andReturn('0');

        $this->assertSame(0, $dao->foundRows());
    }
}
