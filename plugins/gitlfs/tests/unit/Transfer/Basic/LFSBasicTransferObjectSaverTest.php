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

namespace Tuleap\GitLFS\Transfer\Basic;

use League\Flysystem\FilesystemOperator;
use Tuleap\DB\DBConnection;
use Tuleap\GitLFS\LFSObject\LFSObject;
use Tuleap\GitLFS\LFSObject\LFSObjectID;
use Tuleap\GitLFS\LFSObject\LFSObjectPathAllocator;
use Tuleap\GitLFS\LFSObject\LFSObjectRetriever;
use Tuleap\Instrument\Prometheus\Prometheus;

final class LFSBasicTransferObjectSaverTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private FilesystemOperator&\PHPUnit\Framework\MockObject\MockObject $filesystem;
    private DBConnection&\PHPUnit\Framework\MockObject\MockObject $db_connection;
    private LFSObjectRetriever&\PHPUnit\Framework\MockObject\MockObject $lfs_object_retriever;
    private LFSObjectPathAllocator&\PHPUnit\Framework\MockObject\MockObject $path_allocator;
    private Prometheus $prometheus;

    protected function setUp(): void
    {
        $this->filesystem           = $this->createMock(FilesystemOperator::class);
        $this->db_connection        = $this->createMock(DBConnection::class);
        $this->lfs_object_retriever = $this->createMock(LFSObjectRetriever::class);
        $this->path_allocator       = $this->createMock(LFSObjectPathAllocator::class);
        $this->prometheus           = Prometheus::getInMemory();
    }

    public function testObjectIsSavedIfNeeded(): void
    {
        $object_saver = new LFSBasicTransferObjectSaver(
            $this->filesystem,
            $this->db_connection,
            $this->lfs_object_retriever,
            $this->path_allocator,
            $this->prometheus
        );

        $ready_path = 'ready-path';
        $this->path_allocator->method('getPathForReadyToBeAvailableObject')->willReturn($ready_path);
        $temporary_save_path = 'upload-path';
        $this->path_allocator->method('getPathForSaveInProgressObject')->willReturn($temporary_save_path);

        $this->lfs_object_retriever->method('doesLFSObjectExistsForRepository')->willReturn(false);
        $this->filesystem->method('fileExists')->willReturn(false);

        $input_size     = 1024;
        $input_data     = str_repeat('A', $input_size);
        $input_resource = fopen('php://memory', 'rb+');
        fwrite($input_resource, $input_data);
        rewind($input_resource);
        $expected_oid_value = \hash('sha256', $input_data);
        $lfs_object         = new LFSObject(new LFSObjectID($expected_oid_value), $input_size);

        $this->filesystem->method('writeStream')->willReturnCallback(
            /**
             * @param resource $input_stream
             */
            function (string $save_path, $input_stream) use ($temporary_save_path): void {
                self::assertEquals($temporary_save_path, $save_path);
                $destination_resource = fopen('php://memory', 'wb');
                stream_copy_to_stream($input_stream, $destination_resource);
                fclose($destination_resource);
            }
        );

        $this->db_connection->expects(self::once())->method('reconnectAfterALongRunningProcess');

        $this->filesystem->expects(self::once())->method('move')->with($temporary_save_path, $ready_path);
        $this->filesystem->expects(self::once())->method('delete')->with($temporary_save_path);

        $object_saver->saveObject($this->createStub(\GitRepository::class), $lfs_object, $input_resource);
    }

    public function testAlreadySavedObjectIsSkipped(): void
    {
        $object_saver = new LFSBasicTransferObjectSaver(
            $this->filesystem,
            $this->db_connection,
            $this->lfs_object_retriever,
            $this->path_allocator,
            $this->prometheus
        );

        $this->path_allocator->method('getPathForReadyToBeAvailableObject')->willReturn('path');
        $this->lfs_object_retriever->method('doesLFSObjectExistsForRepository')->willReturn(true);

        $this->filesystem->expects(self::never())->method('move');

        $object_saver->saveObject(
            $this->createStub(\GitRepository::class),
            $this->createStub(LFSObject::class),
            fopen('php://memory', 'rb+')
        );
    }

    public function testSaveIsRejectedWhenInputIsNotAResource(): void
    {
        $object_saver = new LFSBasicTransferObjectSaver(
            $this->filesystem,
            $this->db_connection,
            $this->lfs_object_retriever,
            $this->path_allocator,
            $this->prometheus
        );

        $this->path_allocator->method('getPathForReadyToBeAvailableObject')->willReturn('path');
        $this->lfs_object_retriever->method('doesLFSObjectExistsForRepository')->willReturn(false);
        $this->filesystem->method('fileExists')->willReturn(false);

        $this->expectException(\InvalidArgumentException::class);

        $broken_input_resource = false;
        $object_saver->saveObject(
            $this->createStub(\GitRepository::class),
            $this->createStub(LFSObject::class),
            $broken_input_resource
        );
    }

    public function testSaveIsRejectedWhenOIDOfSavedFileDoesNotMatchTheExpectation(): void
    {
        $object_saver = new LFSBasicTransferObjectSaver(
            $this->filesystem,
            $this->db_connection,
            $this->lfs_object_retriever,
            $this->path_allocator,
            $this->prometheus
        );

        $ready_path = 'ready-path';
        $this->path_allocator->method('getPathForReadyToBeAvailableObject')->willReturn($ready_path);
        $temporary_save_path = 'upload-path';
        $this->path_allocator->method('getPathForSaveInProgressObject')->willReturn($temporary_save_path);

        $this->lfs_object_retriever->method('doesLFSObjectExistsForRepository')->willReturn(false);
        $this->filesystem->method('fileExists')->with($ready_path)->willReturn(false);

        $input_size     = 1024;
        $input_data     = str_repeat('A', $input_size);
        $corrupted_data = str_repeat('B', $input_size);
        $input_resource = fopen('php://memory', 'rb+');
        fwrite($input_resource, $corrupted_data);
        rewind($input_resource);
        $expected_oid_value = \hash('sha256', $input_data);
        $lfs_object         = new LFSObject(new LFSObjectID($expected_oid_value), $input_size);

        $this->expectException(LFSBasicTransferObjectIntegrityException::class);

        $this->filesystem->method('writeStream')->willReturnCallback(
            /**
             * @param resource $input_stream
             */
            function (string $save_path, $input_stream) use ($temporary_save_path): void {
                self::assertEquals($temporary_save_path, $save_path);
                $destination_resource = fopen('php://memory', 'wb');
                stream_copy_to_stream($input_stream, $destination_resource);
                fclose($destination_resource);
            }
        );

        $this->db_connection->method('reconnectAfterALongRunningProcess');
        $this->filesystem->expects(self::once())->method('delete')->with($temporary_save_path);

        $object_saver->saveObject($this->createStub(\GitRepository::class), $lfs_object, $input_resource);
    }

    /**
     * @dataProvider objectSizeProvider
     *
     * @param class-string<\Throwable> $excepted_exception
     */
    public function testSaveIsRejectedWhenSizeOfSavedFileDoesNotMatchTheExpectation(
        int $input_size,
        int $object_size,
        string $excepted_exception,
    ): void {
        $object_saver = new LFSBasicTransferObjectSaver(
            $this->filesystem,
            $this->db_connection,
            $this->lfs_object_retriever,
            $this->path_allocator,
            $this->prometheus
        );

        $ready_path = 'ready-path';
        $this->path_allocator->method('getPathForReadyToBeAvailableObject')->willReturn($ready_path);
        $temporary_save_path = 'upload-path';
        $this->path_allocator->method('getPathForSaveInProgressObject')->willReturn($temporary_save_path);

        $this->lfs_object_retriever->method('doesLFSObjectExistsForRepository')->willReturn(false);
        $this->filesystem->method('fileExists')->with($ready_path)->willReturn(false);

        $input_data     = str_repeat('A', $input_size);
        $input_resource = fopen('php://memory', 'rb+');
        fwrite($input_resource, $input_data);
        rewind($input_resource);
        $lfs_object = new LFSObject(
            new LFSObjectID('aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa'),
            $object_size
        );

        $this->filesystem->method('writeStream')->willReturnCallback(
            /**
             * @param resource $input_stream
             */
            function (string $save_path, $input_stream) use ($temporary_save_path): void {
                self::assertEquals($temporary_save_path, $save_path);
                $destination_resource = fopen('php://memory', 'wb');
                stream_copy_to_stream($input_stream, $destination_resource);
                fclose($destination_resource);
            }
        );

        $this->filesystem->expects(self::once())->method('delete')->with($temporary_save_path);
        $this->db_connection->method('reconnectAfterALongRunningProcess');

        $this->expectException($excepted_exception);
        $object_saver->saveObject($this->createStub(\GitRepository::class), $lfs_object, $input_resource);
    }

    public static function objectSizeProvider(): array
    {
        return [
            [1024, 2048, LFSBasicTransferObjectSizeException::class],
            [1024, 512, LFSBasicTransferObjectOutOfBoundSizeException::class],
        ];
    }
}
