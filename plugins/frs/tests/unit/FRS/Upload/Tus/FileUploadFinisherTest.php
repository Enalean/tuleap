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
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use org\bovigo\vfs\vfsStream;
use Project;
use Tuleap\ForgeConfigSandbox;
use Tuleap\FRS\Upload\FileOngoingUploadDao;
use Tuleap\FRS\Upload\UploadPathAllocator;
use Tuleap\Test\DB\DBTransactionExecutorPassthrough;
use Tuleap\Upload\FileAlreadyUploadedInformation;

final class FileUploadFinisherTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use ForgeConfigSandbox;

    private FileUploadFinisher $finisher;
    private LoggerInterface $logger;
    /**
     * @var FRSLogDao&\PHPUnit\Framework\MockObject\MockObject
     */
    private $log_dao;
    /**
     * @var FRSFileDao&\PHPUnit\Framework\MockObject\MockObject
     */
    private $file_dao;
    /**
     * @var FRSReleaseFactory&\PHPUnit\Framework\MockObject\MockObject
     */
    private $release_factory;
    private UploadPathAllocator $path_allocator;
    /**
     * @var FileOngoingUploadDao&\PHPUnit\Framework\MockObject\MockObject
     */
    private $dao;
    /**
     * @var FRSFileFactory&\PHPUnit\Framework\MockObject\MockObject
     */
    private $file_factory;
    private string $tmp_dir;
    /**
     * @var ToBeCreatedFRSFileBuilder&\PHPUnit\Framework\MockObject\MockObject
     */
    private $frs_file_builder;

    /**
     * @before
     */
    public function createInstance(): void
    {
        $this->tmp_dir = vfsStream::setup()->url();
        \ForgeConfig::set('tmp_dir', $this->tmp_dir);

        $this->logger           = new NullLogger();
        $this->log_dao          = $this->createMock(FRSLogDao::class);
        $this->file_dao         = $this->createMock(FRSFileDao::class);
        $this->release_factory  = $this->createMock(FRSReleaseFactory::class);
        $this->path_allocator   = new UploadPathAllocator();
        $this->dao              = $this->createMock(FileOngoingUploadDao::class);
        $this->file_factory     = $this->createMock(FRSFileFactory::class);
        $this->frs_file_builder = $this->createMock(ToBeCreatedFRSFileBuilder::class);

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

        $project = $this->createMock(Project::class);
        $project->method('getID')->willReturn($project_id);

        $release = $this->createMock(FRSRelease::class);
        $release->method('getProject')->willReturn($project);
        $release->method('getReleaseID')->willReturn($release_id);

        $to_be_created_frs_file = $this->createMock(FRSFile::class);

        $to_be_created_frs_file_as_array = [
            'filename' => 'readme.md',
        ];
        $to_be_created_frs_file->method('toArray')->willReturn($to_be_created_frs_file_as_array);

        $this->dao
            ->method('searchFileOngoingUploadById')
            ->with($uploading_id)
            ->willReturn(
                [
                    'release_id' => $release_id,
                    'name'       => 'readme.md',
                    'user_id'    => $user_id,
                    'file_size'  => 123,
                ]
            );

        $this->release_factory
            ->method('getFRSReleaseFromDb')
            ->with($release_id)
            ->willReturn($release);

        $this->frs_file_builder
            ->expects(self::once())
            ->method('buildFRSFile')
            ->with($release, 'readme.md', 123, $user_id)
            ->willReturn($to_be_created_frs_file);

        $this->file_factory
            ->expects(self::once())
            ->method('moveFileForgeFromSrcDir')
            ->with($project, $release, $to_be_created_frs_file, $upload_dir)
            ->willReturn(true);

        $this->file_dao
            ->expects(self::once())
            ->method('createFromArray')
            ->with($to_be_created_frs_file_as_array)
            ->willReturn($created_file_id);

        $this->log_dao
            ->expects(self::once())
            ->method('addLog')
            ->with($user_id, $project_id, $created_file_id, FRSFile::EVT_CREATE);

        $this->dao
            ->expects(self::once())
            ->method('deleteByItemID')
            ->with($uploading_id);

        $this->finisher->finishUploadFile(
            new FileAlreadyUploadedInformation($uploading_id, 'readme.md', 123)
        );
    }
}
