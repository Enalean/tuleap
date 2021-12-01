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

namespace Tuleap\GitLFS\LFSObject;

use League\Flysystem\FilesystemWriter;
use League\Flysystem\UnableToDeleteFile;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Tuleap\Test\DB\DBTransactionExecutorPassthrough;

class LFSObjectRemoverTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use MockeryPHPUnitIntegration;

    private $filesystem;
    private $path_allocator;
    private $dao;

    protected function setUp(): void
    {
        $this->filesystem     = \Mockery::mock(FilesystemWriter::class);
        $this->path_allocator = \Mockery::mock(LFSObjectPathAllocator::class);
        $this->dao            = \Mockery::mock(LFSObjectDAO::class);
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

        $this->dao->shouldReceive('searchUnusedObjects')->andReturns([
            ['id' => 123, 'object_oid' => 'aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa', 'object_size' => 741],
            ['id' => 456, 'object_oid' => 'bbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbb', 'object_size' => 852],
        ]);
        $this->path_allocator->shouldReceive('getPathForAvailableObject')->andReturns('object/path');

        $this->dao->shouldReceive('deleteUnusableReferences')->with($deletion_delay)->once();
        $this->filesystem->shouldReceive('delete')->twice();
        $this->dao->shouldReceive('deleteObjectByID')->twice();

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

        $this->dao->shouldReceive('searchUnusedObjects')->andReturns([
            ['id' => 123, 'object_oid' => 'aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa', 'object_size' => 741],
        ]);
        $this->path_allocator->shouldReceive('getPathForAvailableObject')->andReturns('object/path');

        $this->dao->shouldReceive('deleteUnusableReferences')->with($deletion_delay)->once();
        $this->filesystem->shouldReceive('delete')->andThrow(UnableToDeleteFile::class);
        $this->dao->shouldReceive('deleteObjectByID')->never();

        $this->expectException(UnableToDeleteFile::class);
        $lfs_object_remover->removeDanglingObjects($deletion_delay);
    }
}
