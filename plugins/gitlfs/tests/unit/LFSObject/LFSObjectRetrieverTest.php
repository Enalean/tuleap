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

namespace Tuleap\GitLFS\LFSObject;

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;

class LFSObjectRetrieverTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    private $dao;

    protected function setUp(): void
    {
        $this->dao = \Mockery::mock(LFSObjectDAO::class);
    }

    public function testFilteredObjectsAreRetrieved()
    {
        $objects = [];
        for ($i = 1; $i <= 4; $i++) {
            $oid = \Mockery::mock(LFSObjectID::class);
            $oid->shouldReceive('getValue')->andReturns("oid$i");
            $object = \Mockery::mock(LFSObject::class);
            $object->shouldReceive('getOID')->andReturns($oid);
            $objects[$i] = $object;
        }

        $this->dao->shouldReceive('searchByRepositoryIDAndOIDs')->andReturns([
            ['object_oid' => 'oid2'],
            ['object_oid' => 'oid4']
        ]);

        $object_retriever     = new LFSObjectRetriever($this->dao);
        $repository           = \Mockery::mock(\GitRepository::class);
        $repository->shouldReceive('getId')->andReturns(123);
        $existing_objects     = $object_retriever->getExistingLFSObjectsFromTheSetForRepository($repository, ...$objects);

        $this->assertCount(2, $existing_objects);
        $this->assertContains($objects[2], $existing_objects);
        $this->assertContains($objects[4], $existing_objects);
    }

    public function testExistenceOfLFSObjectInRepository()
    {
        $this->dao->shouldReceive('searchByRepositoryIDAndOIDs')->with(101, ['oid1'])->andReturns([['object_oid' => 'oid1']]);
        $this->dao->shouldReceive('searchByRepositoryIDAndOIDs')->with(101, ['oid2'])->andReturns([]);

        $repository = \Mockery::mock(\GitRepository::class);
        $repository->shouldReceive('getId')->andReturns(101);

        $oid1 = \Mockery::mock(LFSObjectID::class);
        $oid1->shouldReceive('getValue')->andReturns('oid1');
        $object1 = \Mockery::mock(LFSObject::class);
        $object1->shouldReceive('getOID')->andReturns($oid1);
        $oid2 = \Mockery::mock(LFSObjectID::class);
        $oid2->shouldReceive('getValue')->andReturns('oid2');
        $object2 = \Mockery::mock(LFSObject::class);
        $object2->shouldReceive('getOID')->andReturns($oid2);

        $object_retriever = new LFSObjectRetriever($this->dao);

        $this->assertTrue($object_retriever->doesLFSObjectExistsForRepository($repository, $object1));
        $this->assertFalse($object_retriever->doesLFSObjectExistsForRepository($repository, $object2));
    }

    public function testExistenceOfLFSObject()
    {
        $this->dao->shouldReceive('searchByOIDValue')->with('oid1')->andReturns(['object_oid' => 'oid1']);
        $this->dao->shouldReceive('searchByOIDValue')->with('oid2')->andReturns(null);

        $oid1 = \Mockery::mock(LFSObjectID::class);
        $oid1->shouldReceive('getValue')->andReturns('oid1');
        $object1 = \Mockery::mock(LFSObject::class);
        $object1->shouldReceive('getOID')->andReturns($oid1);
        $oid2 = \Mockery::mock(LFSObjectID::class);
        $oid2->shouldReceive('getValue')->andReturns('oid2');
        $object2 = \Mockery::mock(LFSObject::class);
        $object2->shouldReceive('getOID')->andReturns($oid2);

        $object_retriever = new LFSObjectRetriever($this->dao);

        $this->assertTrue($object_retriever->doesLFSObjectExists($object1));
        $this->assertFalse($object_retriever->doesLFSObjectExists($object2));
    }
}
