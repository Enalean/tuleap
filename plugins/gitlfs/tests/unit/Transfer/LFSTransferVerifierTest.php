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

namespace Tuleap\GitLFS\Transfer;

use League\Flysystem\FilesystemOperator;
use Tuleap\GitLFS\LFSObject\LFSObject;
use Tuleap\GitLFS\LFSObject\LFSObjectDAO;
use Tuleap\GitLFS\LFSObject\LFSObjectID;
use Tuleap\GitLFS\LFSObject\LFSObjectPathAllocator;
use Tuleap\GitLFS\LFSObject\LFSObjectRetriever;
use Tuleap\Test\DB\DBTransactionExecutorPassthrough;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class LFSTransferVerifierTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private FilesystemOperator&\PHPUnit\Framework\MockObject\MockObject $filesystem;
    private LFSObjectRetriever&\PHPUnit\Framework\MockObject\Stub $lfs_object_retriever;
    private LFSObjectPathAllocator&\PHPUnit\Framework\MockObject\Stub $path_allocator;
    private LFSObjectDAO&\PHPUnit\Framework\MockObject\MockObject $dao;

    protected function setUp(): void
    {
        $this->filesystem           = $this->createMock(FilesystemOperator::class);
        $this->lfs_object_retriever = $this->createStub(LFSObjectRetriever::class);
        $this->path_allocator       = $this->createStub(LFSObjectPathAllocator::class);
        $this->dao                  = $this->createMock(LFSObjectDAO::class);
    }

    public function testReadyObjectIsMarkedAsAvailable(): void
    {
        $verifier = new LFSTransferVerifier(
            $this->filesystem,
            $this->lfs_object_retriever,
            $this->path_allocator,
            $this->dao,
            new DBTransactionExecutorPassthrough()
        );

        $ready_path = 'ready-path';
        $this->path_allocator->method('getPathForReadyToBeAvailableObject')->willReturn($ready_path);
        $available_path = 'available-path';
        $this->path_allocator->method('getPathForAvailableObject')->willReturn($available_path);

        $this->lfs_object_retriever->method('doesLFSObjectExistsForRepository')->willReturn(false);
        $this->filesystem->method('fileExists')->with($ready_path)->willReturn(true);
        $this->lfs_object_retriever->method('doesLFSObjectExists')->willReturn(false);

        $lfs_object = new LFSObject(
            new LFSObjectID('aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa'),
            10000
        );
        $repository = $this->createStub(\GitRepository::class);
        $repository->method('getId')->willReturn(100);

        $this->dao->expects(self::once())->method('saveObject');
        $this->dao->expects(self::once())->method('saveObjectReference');
        $this->filesystem->expects(self::once())->method('move')->with($ready_path, $available_path);

        $verifier->verifyAndMarkLFSObjectAsAvailable($lfs_object, $repository);
    }

    public function testAlreadyAvailableObjectIsAttachedToTheRepository(): void
    {
        $verifier = new LFSTransferVerifier(
            $this->filesystem,
            $this->lfs_object_retriever,
            $this->path_allocator,
            $this->dao,
            new DBTransactionExecutorPassthrough()
        );

        $ready_path = 'ready-path';
        $this->path_allocator->method('getPathForReadyToBeAvailableObject')->willReturn($ready_path);

        $this->lfs_object_retriever->method('doesLFSObjectExistsForRepository')->willReturn(false);
        $this->filesystem->method('fileExists')->with($ready_path)->willReturn(true);
        $this->lfs_object_retriever->method('doesLFSObjectExists')->willReturn(true);

        $lfs_object = new LFSObject(
            new LFSObjectID('aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa'),
            10000
        );
        $repository = $this->createStub(\GitRepository::class);
        $repository->method('getId')->willReturn(100);

        $this->dao->expects(self::once())->method('saveObjectReferenceByOIDValue');
        $this->filesystem->expects(self::once())->method('delete')->with($ready_path);

        $verifier->verifyAndMarkLFSObjectAsAvailable($lfs_object, $repository);
    }

    public function testObjectAlreadyAvailableForRepositoryIsRemovedFromItsTemporaryPath(): void
    {
        $verifier = new LFSTransferVerifier(
            $this->filesystem,
            $this->lfs_object_retriever,
            $this->path_allocator,
            $this->dao,
            new DBTransactionExecutorPassthrough()
        );

        $ready_path = 'ready-path';
        $this->path_allocator->method('getPathForReadyToBeAvailableObject')->willReturn($ready_path);

        $this->lfs_object_retriever->method('doesLFSObjectExistsForRepository')->willReturn(true);

        $this->filesystem->expects(self::once())->method('delete')->with($ready_path);

        $verifier->verifyAndMarkLFSObjectAsAvailable($this->createStub(LFSObject::class), $this->createStub(\GitRepository::class));
    }
}
