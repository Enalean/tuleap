<?php
/**
 * Copyright (c) Enalean, 2019 - Present. All Rights Reserved.
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

namespace Tuleap\FRS\Upload\Tus;

use FRSFile;
use FRSFileDao;
use FRSFileFactory;
use FRSLogDao;
use FRSRelease;
use FRSReleaseFactory;
use Logger;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use org\bovigo\vfs\vfsStream;
use PHPUnit\Framework\TestCase;
use Project;
use Tuleap\ForgeConfigSandbox;
use Tuleap\FRS\Upload\FileOngoingUploadDao;
use Tuleap\FRS\Upload\UploadPathAllocator;
use Tuleap\Test\DB\DBTransactionExecutorPassthrough;
use Tuleap\Upload\FileAlreadyUploadedInformation;

final class FileUploadFinisherTest extends TestCase
{
    use MockeryPHPUnitIntegration;
    use ForgeConfigSandbox;

    /**
     * @var FileUploadFinisher
     */
    private $finisher;
    /**
     * @var Logger|\Mockery\MockInterface
     */
    private $logger;
    /**
     * @var FRSLogDao|\Mockery\MockInterface
     */
    private $log_dao;
    /**
     * @var FRSFileDao|\Mockery\MockInterface
     */
    private $file_dao;
    /**
     * @var FRSReleaseFactory|\Mockery\MockInterface
     */
    private $release_factory;
    /**
     * @var \Mockery\MockInterface|UploadPathAllocator
     */
    private $path_allocator;
    /**
     * @var \Mockery\MockInterface|FileOngoingUploadDao
     */
    private $dao;
    /**
     * @var FRSFileFactory|\Mockery\MockInterface
     */
    private $file_factory;
    /**
     * @var string
     */
    private $tmp_dir;
    /**
     * @var \Mockery\MockInterface|ToBeCreatedFRSFileBuilder
     */
    private $frs_file_builder;

    /**
     * @before
     */
    public function createInstance(): void
    {
        $this->tmp_dir = vfsStream::setup()->url();
        \ForgeConfig::set('tmp_dir', $this->tmp_dir);

        $this->logger           = \Mockery::mock(\Psr\Log\LoggerInterface::class);
        $this->log_dao          = \Mockery::mock(FRSLogDao::class);
        $this->file_dao         = \Mockery::mock(FRSFileDao::class);
        $this->release_factory  = \Mockery::mock(FRSReleaseFactory::class);
        $this->path_allocator   = new UploadPathAllocator();
        $this->dao              = \Mockery::mock(FileOngoingUploadDao::class);
        $this->file_factory     = \Mockery::mock(FRSFileFactory::class);
        $this->frs_file_builder = \Mockery::mock(ToBeCreatedFRSFileBuilder::class);

        $this->finisher = new FileUploadFinisher(
            $this->logger,
            $this->path_allocator,
            $this->file_factory,
            $this->release_factory,
            $this->dao,
            new DBTransactionExecutorPassthrough(),
            $this->file_dao,
            $this->log_dao,
            $this->frs_file_builder
        );
    }

    public function testFileIsAddedToTheReleaseWhenTheUploadIsComplete(): void
    {
        $upload_dir = "$this->tmp_dir/frs/ongoing-upload/42";
        mkdir($upload_dir, 0777, true);
        touch("$upload_dir/readme.md");

        $uploading_id    = 42;
        $created_file_id = 666;
        $project_id      = 101;
        $user_id         = 107;
        $release_id      = 1000;

        $project = \Mockery::mock(Project::class);
        $project->shouldReceive('getID')->andReturn($project_id);

        $release = \Mockery::mock(FRSRelease::class);
        $release->shouldReceive('getProject')->andReturn($project);
        $release->shouldReceive('getReleaseID')->andReturn($release_id);

        $to_be_created_frs_file = \Mockery::mock(FRSFile::class);

        $to_be_created_frs_file_as_array = [
            'filename' => 'readme.md'
        ];
        $to_be_created_frs_file->shouldReceive('toArray')->andReturn($to_be_created_frs_file_as_array);

        $this->dao
            ->shouldReceive('searchFileOngoingUploadById')
            ->with($uploading_id)
            ->andReturn(
                [
                    'release_id' => $release_id,
                    'name'       => 'readme.md',
                    'user_id'    => $user_id,
                    'file_size'  => 123
                ]
            );

        $this->release_factory
            ->shouldReceive('getFRSReleaseFromDb')
            ->with($release_id)
            ->andReturn($release);

        $this->frs_file_builder
            ->shouldReceive('buildFRSFile')
            ->with($release, 'readme.md', 123, $user_id)
            ->once()
            ->andReturn($to_be_created_frs_file);

        $this->file_factory
            ->shouldReceive('moveFileForgeFromSrcDir')
            ->with($project, $release, $to_be_created_frs_file, $upload_dir)
            ->once()
            ->andReturn(true);

        $this->file_dao
            ->shouldReceive('createFromArray')
            ->with($to_be_created_frs_file_as_array)
            ->once()
            ->andReturn($created_file_id);

        $this->log_dao
            ->shouldReceive('addLog')
            ->with($user_id, $project_id, $created_file_id, FRSFile::EVT_CREATE)
            ->once();

        $this->dao
            ->shouldReceive('deleteByItemID')
            ->with($uploading_id)
            ->once();

        $this->finisher->finishUpload(
            new FileAlreadyUploadedInformation($uploading_id, 'readme.md', 123)
        );
    }
}
