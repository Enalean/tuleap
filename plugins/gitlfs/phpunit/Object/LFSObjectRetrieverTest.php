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

namespace Tuleap\GitLFS\Object;

use PHPUnit\Framework\TestCase;

class LFSObjectRetrieverTest extends TestCase
{
    private $dao;

    protected function setUp()
    {
        $this->dao = \Mockery::mock(LFSObjectDAO::class);
    }

    public function testExistenceOfLFSObject()
    {
        $this->dao->shouldReceive('searchByOIDs')->with(['oid1'])->andReturns([['object_oid' => 'oid1']]);
        $this->dao->shouldReceive('searchByOIDs')->with(['oid2'])->andReturns([]);

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
