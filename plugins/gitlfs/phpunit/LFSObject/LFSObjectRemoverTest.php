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

use League\Flysystem\FileNotFoundException;
use League\Flysystem\FilesystemInterface;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;

class LFSObjectRemoverTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    private $filesystem;
    private $path_allocator;
    private $dao;
    private $mockery_matcher_callback_wrapped_operations;

    protected function setUp()
    {
        $this->filesystem     = \Mockery::mock(FilesystemInterface::class);
        $this->path_allocator = \Mockery::mock(LFSObjectPathAllocator::class);
        $this->dao            = \Mockery::mock(LFSObjectDAO::class);
        $this->mockery_matcher_callback_wrapped_operations = \Mockery::on(
            function (callable $operations) {
                $operations($this->dao);
                return true;
            }
        );
    }

    public function testDanglingObjectsAreRemoved()
    {
        $lfs_object_remover = new LFSObjectRemover($this->dao, $this->filesystem, $this->path_allocator);

        $this->dao->shouldReceive('wrapAtomicOperations')
            ->with($this->mockery_matcher_callback_wrapped_operations);
        $this->dao->shouldReceive('searchUnusedObjects')->andReturns([
            ['id' => 123, 'object_oid' => 'aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa', 'object_size' => 741],
            ['id' => 456, 'object_oid' => 'bbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbb', 'object_size' => 852],
        ]);
        $this->path_allocator->shouldReceive('getPathForAvailableObject')->andReturns('object/path');

        $this->dao->shouldReceive('deleteUnusableReferences')->once();
        $this->filesystem->shouldReceive('delete')->andReturns(true)->twice();
        $this->dao->shouldReceive('deleteObjectByID')->twice();

        $lfs_object_remover->removeDanglingObjects();
    }

    public function testRemovingADanglingObjectNotPresentOnTheFilesystemWorks()
    {
        $lfs_object_remover = new LFSObjectRemover($this->dao, $this->filesystem, $this->path_allocator);

        $this->dao->shouldReceive('wrapAtomicOperations')
            ->with($this->mockery_matcher_callback_wrapped_operations);
        $this->dao->shouldReceive('searchUnusedObjects')->andReturns([
            ['id' => 123, 'object_oid' => 'aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa', 'object_size' => 741]
        ]);
        $this->path_allocator->shouldReceive('getPathForAvailableObject')->andReturns('object/path');

        $this->dao->shouldReceive('deleteUnusableReferences')->once();
        $this->filesystem->shouldReceive('delete')->andThrows(FileNotFoundException::class);
        $this->dao->shouldReceive('deleteObjectByID')->once();

        $lfs_object_remover->removeDanglingObjects();
    }

    public function testReferenceToTheDanglingObjectIsKeptWhenDeletionFails()
    {
        $lfs_object_remover = new LFSObjectRemover($this->dao, $this->filesystem, $this->path_allocator);

        $this->dao->shouldReceive('wrapAtomicOperations')
            ->with($this->mockery_matcher_callback_wrapped_operations);
        $this->dao->shouldReceive('searchUnusedObjects')->andReturns([
            ['id' => 123, 'object_oid' => 'aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa', 'object_size' => 741]
        ]);
        $this->path_allocator->shouldReceive('getPathForAvailableObject')->andReturns('object/path');

        $this->dao->shouldReceive('deleteUnusableReferences')->once();
        $this->filesystem->shouldReceive('delete')->andReturns(false);
        $this->dao->shouldReceive('deleteObjectByID')->never();

        $lfs_object_remover->removeDanglingObjects();
    }
}
