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

namespace Tuleap\GitLFS\Transfer\Basic;

use League\Flysystem\FilesystemOperator;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Tuleap\DB\DBConnection;
use Tuleap\GitLFS\LFSObject\LFSObject;
use Tuleap\GitLFS\LFSObject\LFSObjectID;
use Tuleap\GitLFS\LFSObject\LFSObjectPathAllocator;
use Tuleap\GitLFS\LFSObject\LFSObjectRetriever;
use Tuleap\Instrument\Prometheus\Prometheus;

class LFSBasicTransferObjectSaverTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use MockeryPHPUnitIntegration;

    private $filesystem;
    private $db_connection;
    private $lfs_object_retriever;
    private $path_allocator;
    private $prometheus;

    protected function setUp(): void
    {
        $this->filesystem           = \Mockery::mock(FilesystemOperator::class);
        $this->db_connection        = \Mockery::mock(DBConnection::class);
        $this->lfs_object_retriever = \Mockery::mock(LFSObjectRetriever::class);
        $this->path_allocator       = \Mockery::mock(LFSObjectPathAllocator::class);
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
        $this->path_allocator->shouldReceive('getPathForReadyToBeAvailableObject')->andReturns($ready_path);
        $temporary_save_path = 'upload-path';
        $this->path_allocator->shouldReceive('getPathForSaveInProgressObject')->andReturns($temporary_save_path);

        $this->lfs_object_retriever->shouldReceive('doesLFSObjectExistsForRepository')->andReturns(false);
        $this->filesystem->shouldReceive('fileExists')->with($ready_path)->andReturns(false);

        $input_size     = 1024;
        $input_data     = str_repeat('A', $input_size);
        $input_resource = fopen('php://memory', 'rb+');
        fwrite($input_resource, $input_data);
        rewind($input_resource);
        $expected_oid_value = \hash('sha256', $input_data);
        $lfs_object         = new LFSObject(new LFSObjectID($expected_oid_value), $input_size);

        $this->filesystem->shouldReceive('writeStream')->with($temporary_save_path, \Mockery::any())->andReturnUsing(
            function ($save_path, $input_stream): void {
                $destination_resource = fopen('php://memory', 'wb');
                stream_copy_to_stream($input_stream, $destination_resource);
                fclose($destination_resource);
            }
        );

        $this->db_connection->shouldReceive('reconnectAfterALongRunningProcess')->once();

        $this->filesystem->shouldReceive('move')->with($temporary_save_path, $ready_path)->once();
        $this->filesystem->shouldReceive('delete')->with($temporary_save_path)->once();

        $object_saver->saveObject(\Mockery::mock(\GitRepository::class), $lfs_object, $input_resource);
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

        $this->path_allocator->shouldReceive('getPathForReadyToBeAvailableObject')->andReturns('path');
        $this->lfs_object_retriever->shouldReceive('doesLFSObjectExistsForRepository')->andReturns(true);

        $this->filesystem->shouldReceive('move')->never();

        $object_saver->saveObject(
            \Mockery::mock(\GitRepository::class),
            \Mockery::mock(LFSObject::class),
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

        $this->path_allocator->shouldReceive('getPathForReadyToBeAvailableObject')->andReturns('path');
        $this->lfs_object_retriever->shouldReceive('doesLFSObjectExistsForRepository')->andReturns(false);
        $this->filesystem->shouldReceive('fileExists')->andReturns(false);

        $this->expectException(\InvalidArgumentException::class);

        $broken_input_resource = false;
        $object_saver->saveObject(
            \Mockery::mock(\GitRepository::class),
            \Mockery::mock(LFSObject::class),
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
        $this->path_allocator->shouldReceive('getPathForReadyToBeAvailableObject')->andReturns($ready_path);
        $temporary_save_path = 'upload-path';
        $this->path_allocator->shouldReceive('getPathForSaveInProgressObject')->andReturns($temporary_save_path);

        $this->lfs_object_retriever->shouldReceive('doesLFSObjectExistsForRepository')->andReturns(false);
        $this->filesystem->shouldReceive('fileExists')->with($ready_path)->andReturns(false);

        $input_size     = 1024;
        $input_data     = str_repeat('A', $input_size);
        $corrupted_data = str_repeat('B', $input_size);
        $input_resource = fopen('php://memory', 'rb+');
        fwrite($input_resource, $corrupted_data);
        rewind($input_resource);
        $expected_oid_value = \hash('sha256', $input_data);
        $lfs_object         = new LFSObject(new LFSObjectID($expected_oid_value), $input_size);

        $this->expectException(LFSBasicTransferObjectIntegrityException::class);

        $this->filesystem->shouldReceive('writeStream')->with($temporary_save_path, \Mockery::any())->andReturnUsing(
            function ($save_path, $input_stream): void {
                $destination_resource = fopen('php://memory', 'wb');
                stream_copy_to_stream($input_stream, $destination_resource);
                fclose($destination_resource);
            }
        );

        $this->db_connection->shouldReceive('reconnectAfterALongRunningProcess');
        $this->filesystem->shouldReceive('delete')->with($temporary_save_path)->once();

        $object_saver->saveObject(\Mockery::mock(\GitRepository::class), $lfs_object, $input_resource);
    }

    /**
     * @dataProvider objectSizeProvider
     */
    public function testSaveIsRejectedWhenSizeOfSavedFileDoesNotMatchTheExpectation(
        $input_size,
        $object_size,
        $excepted_exception,
    ): void {
        $object_saver = new LFSBasicTransferObjectSaver(
            $this->filesystem,
            $this->db_connection,
            $this->lfs_object_retriever,
            $this->path_allocator,
            $this->prometheus
        );

        $ready_path = 'ready-path';
        $this->path_allocator->shouldReceive('getPathForReadyToBeAvailableObject')->andReturns($ready_path);
        $temporary_save_path = 'upload-path';
        $this->path_allocator->shouldReceive('getPathForSaveInProgressObject')->andReturns($temporary_save_path);

        $this->lfs_object_retriever->shouldReceive('doesLFSObjectExistsForRepository')->andReturns(false);
        $this->filesystem->shouldReceive('fileExists')->with($ready_path)->andReturns(false);

        $input_data     = str_repeat('A', $input_size);
        $input_resource = fopen('php://memory', 'rb+');
        fwrite($input_resource, $input_data);
        rewind($input_resource);
        $lfs_object = new LFSObject(
            new LFSObjectID('aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa'),
            $object_size
        );

        $this->filesystem->shouldReceive('writeStream')->with($temporary_save_path, \Mockery::any())->andReturnUsing(
            function ($save_path, $input_stream): void {
                $destination_resource = fopen('php://memory', 'wb');
                stream_copy_to_stream($input_stream, $destination_resource);
                fclose($destination_resource);
            }
        );

        $this->filesystem->shouldReceive('delete')->with($temporary_save_path)->once();
        $this->db_connection->shouldReceive('reconnectAfterALongRunningProcess');

        $this->expectException($excepted_exception);
        $object_saver->saveObject(\Mockery::mock(\GitRepository::class), $lfs_object, $input_resource);
    }

    public function objectSizeProvider()
    {
        return [
            [1024, 2048, LFSBasicTransferObjectSizeException::class],
            [1024, 512, LFSBasicTransferObjectOutOfBoundSizeException::class],
        ];
    }
}
