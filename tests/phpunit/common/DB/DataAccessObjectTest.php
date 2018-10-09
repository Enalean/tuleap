<?php
/**
 * Copyright (c) Enalean, 2018. All Rights Reserved.
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

namespace Tuleap\DB;

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use ParagonIE\EasyDB\EasyDB;
use PHPUnit\Framework\TestCase;

class DataAccessObjectTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var \Mockery\MockInterface
     */
    private $db;
    private $atomic_operations;

    protected function setUp()
    {
        $this->db                = \Mockery::mock(EasyDB::class);
        $this->atomic_operations = $this->createPartialMock(
            \stdClass::class,
            ['__invoke']
        );
    }

    public function testAtomicOperationsAreCommittedWhenNoErrorIsEncountered()
    {
        $dao = \Mockery::mock(DataAccessObject::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $dao->shouldReceive('getDB')->andReturns($this->db);

        $this->db->shouldReceive('inTransaction')->andReturns(false);
        $this->db->shouldReceive('beginTransaction')->once();
        $this->db->shouldReceive('commit')->once();

        $this->atomic_operations->expects($this->once())->method('__invoke')->with($dao);

        $dao->wrapAtomicOperations($this->atomic_operations);
    }

    /**
     * @expectedException \Exception
     */
    public function testAtomicOperationsAreRollbackWhenAnErrorIsEncountered()
    {
        $dao = \Mockery::mock(DataAccessObject::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $dao->shouldReceive('getDB')->andReturns($this->db);

        $this->db->shouldReceive('inTransaction')->andReturns(false);
        $this->db->shouldReceive('beginTransaction')->once();
        $this->db->shouldReceive('rollBack')->once();

        $this->atomic_operations->expects($this->once())->method('__invoke')->with($dao)->willThrowException(
            new \Exception()
        );

        $dao->wrapAtomicOperations($this->atomic_operations);
    }

    public function testTransactionIsNotReopenIfOneIsOngoing()
    {
        $dao = \Mockery::mock(DataAccessObject::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $dao->shouldReceive('getDB')->andReturns($this->db);

        $this->db->shouldReceive('inTransaction')->andReturns(true);
        $this->db->shouldReceive('beginTransaction')->never();
        $this->db->shouldReceive('commit')->never();
        $this->db->shouldReceive('rollBack')->never();

        $this->atomic_operations->expects($this->once())->method('__invoke')->with($dao);

        $dao->wrapAtomicOperations($this->atomic_operations);
    }
}
