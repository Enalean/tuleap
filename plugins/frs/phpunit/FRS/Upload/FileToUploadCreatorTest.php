<?php
/**
 * Copyright (c) Enalean, 2018-2019. All Rights Reserved.
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

namespace Tuleap\FRS\Upload;

use DateTimeImmutable;
use FRSFileFactory;
use FRSRelease;
use LogicException;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Mockery\MockInterface;
use PFUser;
use PHPUnit\Framework\TestCase;
use Tuleap\ForgeConfigSandbox;
use Tuleap\Test\DB\DBTransactionExecutorPassthrough;

class FileToUploadCreatorTest extends TestCase
{
    use MockeryPHPUnitIntegration;
    use ForgeConfigSandbox;

    private const RELEASE_ID = 11;
    private const PROJECT_ID = 104;

    /**
     * @var MockInterface|FileOngoingUploadDao
     */
    private $dao;
    /**
     * @var FileToUploadCreator
     */
    private $creator;
    /**
     * @var FRSFileFactory|MockInterface
     */
    private $file_factory;
    /**
     * @var FRSRelease|MockInterface
     */
    private $release;
    /**
     * @var MockInterface|PFUser
     */
    private $user;

    /**
     * @before
     */
    public function instantiateCreator(): void
    {
        $this->dao          = Mockery::mock(FileOngoingUploadDao::class);
        $this->file_factory = Mockery::mock(FRSFileFactory::class);
        $this->creator      = new FileToUploadCreator(
            $this->file_factory,
            $this->dao,
            new DBTransactionExecutorPassthrough(),
            1000
        );
    }

    /**
     * @before
     */
    public function instantiateRelease(): void
    {
        $this->release = Mockery::mock(FRSRelease::class);
        $this->release->shouldReceive('getReleaseId')->andReturn(self::RELEASE_ID);
        $this->release->shouldReceive('getGroupId')->andReturn(self::PROJECT_ID);
    }

    /**
     * @before
     */
    public function instantiateUser(): void
    {
        $this->user = Mockery::mock(PFUser::class);
        $this->user->shouldReceive('getId')->andReturn(102);
    }

    public function testCreation()
    {
        $current_time = new DateTimeImmutable();

        $this->file_factory
            ->shouldReceive('isFileBaseNameExists')
            ->with('filename.txt', self::RELEASE_ID, self::PROJECT_ID)
            ->andReturn(false);
        $this->file_factory
            ->shouldReceive('isSameFileMarkedToBeRestored')
            ->with('filename.txt', self::RELEASE_ID)
            ->andReturn(false);

        $this->dao->shouldReceive('searchFileOngoingUploadByReleaseIDNameAndExpirationDate')->andReturn([]);
        $this->dao->shouldReceive('saveFileOngoingUpload')->once()->andReturn(12);

        $document_to_upload = $this->creator->create(
            $this->release,
            $this->user,
            $current_time,
            'filename.txt',
            123
        );

        $this->assertSame('/uploads/frs/file/12', $document_to_upload->getUploadHref());
    }

    public function testANewItemIsNotCreatedIfAFileWithSameNameExists()
    {
        $current_time = new DateTimeImmutable();

        $this->file_factory
            ->shouldReceive('isFileBaseNameExists')
            ->with('filename.txt', self::RELEASE_ID, self::PROJECT_ID)
            ->andReturn(true);
        $this->file_factory
            ->shouldReceive('isSameFileMarkedToBeRestored')
            ->with('filename.txt', self::RELEASE_ID)
            ->andReturn(false);

        $this->dao->shouldReceive('saveFileOngoingUpload')->never();

        $this->expectException(UploadFileNameAlreadyExistsException::class);

        $this->creator->create(
            $this->release,
            $this->user,
            $current_time,
            'filename.txt',
            123
        );
    }

    public function testANewItemIsNotCreatedIfAFileWithSameNameExistsIsMarkedAsRestored()
    {
        $current_time = new DateTimeImmutable();

        $this->file_factory
            ->shouldReceive('isFileBaseNameExists')
            ->with('filename.txt', self::RELEASE_ID, self::PROJECT_ID)
            ->andReturn(false);
        $this->file_factory
            ->shouldReceive('isSameFileMarkedToBeRestored')
            ->with('filename.txt', self::RELEASE_ID)
            ->andReturn(true);

        $this->dao->shouldReceive('saveFileOngoingUpload')->never();

        $this->expectException(UploadFileMarkedToBeRestoredException::class);

        $this->creator->create(
            $this->release,
            $this->user,
            $current_time,
            'filename.txt',
            123
        );
    }

    public function testANewItemIsNotCreatedIfAnUploadIsOngoingWithTheSameFile()
    {
        $current_time = new DateTimeImmutable();

        $this->file_factory
            ->shouldReceive('isFileBaseNameExists')
            ->with('filename.txt', self::RELEASE_ID, self::PROJECT_ID)
            ->andReturn(false);
        $this->file_factory
            ->shouldReceive('isSameFileMarkedToBeRestored')
            ->with('filename.txt', self::RELEASE_ID)
            ->andReturn(false);

        $this->dao->shouldReceive('searchFileOngoingUploadByReleaseIDNameAndExpirationDate')->andReturn(
            [
                ['id' => 12, 'user_id' => 102, 'file_size' => 123]
            ]
        );

        $document_to_upload = $this->creator->create(
            $this->release,
            $this->user,
            $current_time,
            'filename.txt',
            123
        );

        $this->assertSame('/uploads/frs/file/12', $document_to_upload->getUploadHref());
    }

    public function testANewItemIsNotCreatedIfManyUploadsAreOngoingWithTheSameFileWhichShouldNeverHappen()
    {
        $current_time = new DateTimeImmutable();

        $this->file_factory
            ->shouldReceive('isFileBaseNameExists')
            ->with('filename.txt', self::RELEASE_ID, self::PROJECT_ID)
            ->andReturn(false);
        $this->file_factory
            ->shouldReceive('isSameFileMarkedToBeRestored')
            ->with('filename.txt', self::RELEASE_ID)
            ->andReturn(false);

        $this->dao->shouldReceive('searchFileOngoingUploadByReleaseIDNameAndExpirationDate')->andReturn(
            [
                ['id' => 12, 'user_id' => 102, 'file_size' => 123],
                ['id' => 13, 'user_id' => 102, 'file_size' => 123]
            ]
        );

        $this->dao->shouldReceive('saveFileOngoingUpload')->never();

        $this->expectException(LogicException::class);

        $this->creator->create(
            $this->release,
            $this->user,
            $current_time,
            'filename.txt',
            123
        );
    }

    public function testCreationIsRejectedWhenAnotherUserIsCreatingTheDocument()
    {
        $current_time = new DateTimeImmutable();

        $this->file_factory
            ->shouldReceive('isFileBaseNameExists')
            ->with('filename.txt', self::RELEASE_ID, self::PROJECT_ID)
            ->andReturn(false);
        $this->file_factory
            ->shouldReceive('isSameFileMarkedToBeRestored')
            ->with('filename.txt', self::RELEASE_ID)
            ->andReturn(false);

        $this->dao->shouldReceive('searchFileOngoingUploadByReleaseIDNameAndExpirationDate')->andReturn(
            [
                ['user_id' => 103]
            ]
        );

        $this->expectException(UploadCreationConflictException::class);

        $this->creator->create(
            $this->release,
            $this->user,
            $current_time,
            'filename.txt',
            123
        );
    }

    public function testCreationIsRejectedWhenTheUserIsAlreadyCreatingTheDocumentWithAnotherFile()
    {
        $current_time = new DateTimeImmutable();

        $this->file_factory
            ->shouldReceive('isFileBaseNameExists')
            ->with('filename.txt', self::RELEASE_ID, self::PROJECT_ID)
            ->andReturn(false);
        $this->file_factory
            ->shouldReceive('isSameFileMarkedToBeRestored')
            ->with('filename.txt', self::RELEASE_ID)
            ->andReturn(false);

        $this->dao->shouldReceive('searchFileOngoingUploadByReleaseIDNameAndExpirationDate')->andReturn(
            [
                ['user_id' => 102, 'file_size' => 123456]
            ]
        );

        $this->expectException(UploadCreationFileMismatchException::class);

        $this->creator->create(
            $this->release,
            $this->user,
            $current_time,
            'filename.txt',
            789
        );
    }

    public function testCreationIsRejectedIfTheFileIsBiggerThanTheConfigurationLimit()
    {
        $current_time = new DateTimeImmutable();

        $this->file_factory
            ->shouldReceive('isFileBaseNameExists')
            ->with('filename.txt', self::RELEASE_ID, self::PROJECT_ID)
            ->andReturn(false);
        $this->file_factory
            ->shouldReceive('isSameFileMarkedToBeRestored')
            ->with('filename.txt', self::RELEASE_ID)
            ->andReturn(false);

        $this->expectException(UploadMaxSizeExceededException::class);

        $this->creator->create(
            $this->release,
            $this->user,
            $current_time,
            'filename.txt',
            2000
        );
    }
}
