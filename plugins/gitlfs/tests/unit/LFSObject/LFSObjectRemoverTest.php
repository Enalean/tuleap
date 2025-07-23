<?php
/**
 * Copyright (c) Enalean, 2018-Present. All Rights Reserved.
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

namespace Tuleap\GitLFS\LFSObject;

use League\Flysystem\FilesystemWriter;
use League\Flysystem\UnableToDeleteFile;
use Tuleap\Test\DB\DBTransactionExecutorPassthrough;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class LFSObjectRemoverTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private FilesystemWriter&\PHPUnit\Framework\MockObject\MockObject $filesystem;
    private LFSObjectPathAllocator&\PHPUnit\Framework\MockObject\Stub $path_allocator;
    private LFSObjectDAO&\PHPUnit\Framework\MockObject\MockObject $dao;

    #[\Override]
    protected function setUp(): void
    {
        $this->filesystem     = $this->createMock(FilesystemWriter::class);
        $this->path_allocator = $this->createStub(LFSObjectPathAllocator::class);
        $this->dao            = $this->createMock(LFSObjectDAO::class);
    }

    public function testDanglingObjectsAreRemoved(): void
    {
        $deletion_delay     = 3;
        $lfs_object_remover = new LFSObjectRemover(
            $this->dao,
            new DBTransactionExecutorPassthrough(),
            $this->filesystem,
            $this->path_allocator
        );

        $this->dao->method('searchUnusedObjects')->willReturn([
            ['id' => 123, 'object_oid' => 'aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa', 'object_size' => 741],
            ['id' => 456, 'object_oid' => 'bbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbb', 'object_size' => 852],
        ]);
        $this->path_allocator->method('getPathForAvailableObject')->willReturn('object/path');

        $this->dao->expects($this->once())->method('deleteUnusableReferences')->with($deletion_delay);
        $this->filesystem->expects($this->exactly(2))->method('delete');
        $this->dao->expects($this->exactly(2))->method('deleteObjectByID');

        $lfs_object_remover->removeDanglingObjects($deletion_delay);
    }

    public function testReferenceToTheDanglingObjectIsKeptWhenDeletionFails(): void
    {
        $deletion_delay     = 3;
        $lfs_object_remover = new LFSObjectRemover(
            $this->dao,
            new DBTransactionExecutorPassthrough(),
            $this->filesystem,
            $this->path_allocator
        );

        $this->dao->method('searchUnusedObjects')->willReturn([
            ['id' => 123, 'object_oid' => 'aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa', 'object_size' => 741],
        ]);
        $this->path_allocator->method('getPathForAvailableObject')->willReturn('object/path');

        $this->dao->expects($this->once())->method('deleteUnusableReferences')->with($deletion_delay);
        $this->filesystem->method('delete')->willThrowException(new UnableToDeleteFile());
        $this->dao->expects($this->never())->method('deleteObjectByID');

        $this->expectException(UnableToDeleteFile::class);
        $lfs_object_remover->removeDanglingObjects($deletion_delay);
    }
}
