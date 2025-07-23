<?php
/**
 * Copyright (c) Enalean, 2018 - Present. All Rights Reserved.
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

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class LFSObjectRetrieverTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private LFSObjectDAO&\PHPUnit\Framework\MockObject\MockObject $dao;

    #[\Override]
    protected function setUp(): void
    {
        $this->dao = $this->createMock(LFSObjectDAO::class);
    }

    public function testFilteredObjectsAreRetrieved(): void
    {
        $objects = [];
        for ($i = 1; $i <= 4; $i++) {
            $oid = $this->createStub(LFSObjectID::class);
            $oid->method('getValue')->willReturn("oid$i");
            $object = $this->createStub(LFSObject::class);
            $object->method('getOID')->willReturn($oid);
            $objects[$i] = $object;
        }

        $this->dao->method('searchByRepositoryIDAndOIDs')->willReturn([
            ['object_oid' => 'oid2'],
            ['object_oid' => 'oid4'],
        ]);

        $object_retriever = new LFSObjectRetriever($this->dao);
        $repository       = $this->createStub(\GitRepository::class);
        $repository->method('getId')->willReturn(123);
        $existing_objects = $object_retriever->getExistingLFSObjectsFromTheSetForRepository($repository, ...$objects);

        $this->assertCount(2, $existing_objects);
        $this->assertContains($objects[2], $existing_objects);
        $this->assertContains($objects[4], $existing_objects);
    }

    public function testExistenceOfLFSObjectInRepository(): void
    {
        $this->dao->method('searchByRepositoryIDAndOIDs')->willReturnCallback(
            fn (int $repository_id, array $oids): array => match ([$repository_id, $oids]) {
                [101, ['oid1']] => [['object_oid' => 'oid1']],
                [101, ['oid2']] => [],
            }
        );

        $repository = $this->createStub(\GitRepository::class);
        $repository->method('getId')->willReturn(101);

        $oid1 = $this->createStub(LFSObjectID::class);
        $oid1->method('getValue')->willReturn('oid1');
        $object1 = $this->createStub(LFSObject::class);
        $object1->method('getOID')->willReturn($oid1);
        $oid2 = $this->createStub(LFSObjectID::class);
        $oid2->method('getValue')->willReturn('oid2');
        $object2 = $this->createStub(LFSObject::class);
        $object2->method('getOID')->willReturn($oid2);

        $object_retriever = new LFSObjectRetriever($this->dao);

        $this->assertTrue($object_retriever->doesLFSObjectExistsForRepository($repository, $object1));
        $this->assertFalse($object_retriever->doesLFSObjectExistsForRepository($repository, $object2));
    }

    public function testExistenceOfLFSObject(): void
    {
        $this->dao->method('searchByOIDValue')->willReturnCallback(
            fn (string $oid_value): ?array => match ($oid_value) {
                'oid1' => [['object_oid' => 'oid1']],
                'oid2' => null,
            }
        );

        $oid1 = $this->createStub(LFSObjectID::class);
        $oid1->method('getValue')->willReturn('oid1');
        $object1 = $this->createStub(LFSObject::class);
        $object1->method('getOID')->willReturn($oid1);
        $oid2 = $this->createStub(LFSObjectID::class);
        $oid2->method('getValue')->willReturn('oid2');
        $object2 = $this->createStub(LFSObject::class);
        $object2->method('getOID')->willReturn($oid2);

        $object_retriever = new LFSObjectRetriever($this->dao);

        $this->assertTrue($object_retriever->doesLFSObjectExists($object1));
        $this->assertFalse($object_retriever->doesLFSObjectExists($object2));
    }
}
