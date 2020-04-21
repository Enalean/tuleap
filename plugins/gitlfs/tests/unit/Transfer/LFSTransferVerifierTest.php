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

namespace Tuleap\GitLFS\Transfer;

use League\Flysystem\FilesystemInterface;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Tuleap\GitLFS\LFSObject\LFSObject;
use Tuleap\GitLFS\LFSObject\LFSObjectDAO;
use Tuleap\GitLFS\LFSObject\LFSObjectID;
use Tuleap\GitLFS\LFSObject\LFSObjectPathAllocator;
use Tuleap\GitLFS\LFSObject\LFSObjectRetriever;
use Tuleap\Test\DB\DBTransactionExecutorPassthrough;

class LFSTransferVerifierTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    private $filesystem;
    private $lfs_object_retriever;
    private $path_allocator;
    private $dao;

    protected function setUp(): void
    {
        $this->filesystem           = \Mockery::mock(FilesystemInterface::class);
        $this->lfs_object_retriever = \Mockery::mock(LFSObjectRetriever::class);
        $this->path_allocator       = \Mockery::mock(LFSObjectPathAllocator::class);
        $this->dao                  = \Mockery::mock(LFSObjectDAO::class);
    }

    public function testReadyObjectIsMarkedAsAvailable()
    {
        $verifier = new LFSTransferVerifier(
            $this->filesystem,
            $this->lfs_object_retriever,
            $this->path_allocator,
            $this->dao,
            new DBTransactionExecutorPassthrough()
        );

        $ready_path = 'ready-path';
        $this->path_allocator->shouldReceive('getPathForReadyToBeAvailableObject')->andReturns($ready_path);
        $available_path = 'available-path';
        $this->path_allocator->shouldReceive('getPathForAvailableObject')->andReturns($available_path);

        $this->lfs_object_retriever->shouldReceive('doesLFSObjectExistsForRepository')->andReturns(false);
        $this->filesystem->shouldReceive('has')->with($ready_path)->andReturns(true);
        $this->lfs_object_retriever->shouldReceive('doesLFSObjectExists')->andReturns(false);

        $lfs_object = $lfs_object = new LFSObject(
            new LFSObjectID('aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa'),
            10000
        );
        $repository = \Mockery::mock(\GitRepository::class);
        $repository->shouldReceive('getId')->andReturns(100);

        $this->dao->shouldReceive('saveObject')->once();
        $this->dao->shouldReceive('saveObjectReference')->once();
        $this->filesystem->shouldReceive('rename')->with($ready_path, $available_path)->andReturns(true)->once();

        $verifier->verifyAndMarkLFSObjectAsAvailable($lfs_object, $repository);
    }

    public function testAlreadyAvailableObjectIsAttachedToTheRepository()
    {
        $verifier = new LFSTransferVerifier(
            $this->filesystem,
            $this->lfs_object_retriever,
            $this->path_allocator,
            $this->dao,
            new DBTransactionExecutorPassthrough()
        );

        $ready_path = 'ready-path';
        $this->path_allocator->shouldReceive('getPathForReadyToBeAvailableObject')->andReturns($ready_path);

        $this->lfs_object_retriever->shouldReceive('doesLFSObjectExistsForRepository')->andReturns(false);
        $this->filesystem->shouldReceive('has')->with($ready_path)->andReturns(true);
        $this->lfs_object_retriever->shouldReceive('doesLFSObjectExists')->andReturns(true);

        $lfs_object = $lfs_object = new LFSObject(
            new LFSObjectID('aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa'),
            10000
        );
        $repository = \Mockery::mock(\GitRepository::class);
        $repository->shouldReceive('getId')->andReturns(100);

        $this->dao->shouldReceive('saveObjectReferenceByOIDValue')->once();
        $this->filesystem->shouldReceive('delete')->with($ready_path)->andReturns(true)->once();

        $verifier->verifyAndMarkLFSObjectAsAvailable($lfs_object, $repository);
    }

    public function testObjectAlreadyAvailableForRepositoryIsRemovedFromItsTemporaryPath()
    {
        $verifier = new LFSTransferVerifier(
            $this->filesystem,
            $this->lfs_object_retriever,
            $this->path_allocator,
            $this->dao,
            new DBTransactionExecutorPassthrough()
        );

        $ready_path = 'ready-path';
        $this->path_allocator->shouldReceive('getPathForReadyToBeAvailableObject')->andReturns($ready_path);

        $this->lfs_object_retriever->shouldReceive('doesLFSObjectExistsForRepository')->andReturns(true);

        $this->filesystem->shouldReceive('delete')->with($ready_path)->andReturns(true)->once();

        $verifier->verifyAndMarkLFSObjectAsAvailable(\Mockery::mock(LFSObject::class), \Mockery::mock(\GitRepository::class));
    }
}
