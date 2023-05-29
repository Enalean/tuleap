<?php
/**
 * Copyright (c) Enalean, 2018 - Present. All Rights Reserved.
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
use PFUser;
use Tuleap\ForgeConfigSandbox;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\DB\DBTransactionExecutorPassthrough;

final class FileToUploadCreatorTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use ForgeConfigSandbox;

    private const RELEASE_ID = 11;
    private const PROJECT_ID = 104;

    /**
     * @var FileOngoingUploadDao&\PHPUnit\Framework\MockObject\MockObject
     */
    private $dao;
    private FileToUploadCreator $creator;
    /**
     * @var FRSFileFactory&\PHPUnit\Framework\MockObject\MockObject
     */
    private $file_factory;
    /**
     * @var FRSRelease&\PHPUnit\Framework\MockObject\MockObject
     */
    private $release;
    private PFUser $user;

    /**
     * @before
     */
    public function instantiateCreator(): void
    {
        $this->dao          = $this->createMock(FileOngoingUploadDao::class);
        $this->file_factory = $this->createMock(FRSFileFactory::class);
        $this->creator      = new FileToUploadCreator(
            $this->file_factory,
            $this->dao,
            new DBTransactionExecutorPassthrough(),
            1000
        );

        $this->user = UserTestBuilder::aUser()->withId(102)->build();
    }

    /**
     * @before
     */
    public function instantiateRelease(): void
    {
        $this->release = $this->createMock(FRSRelease::class);
        $this->release->method('getReleaseId')->willReturn(self::RELEASE_ID);
        $this->release->method('getGroupId')->willReturn(self::PROJECT_ID);
    }

    public function testCreation(): void
    {
        $current_time = new DateTimeImmutable();

        $this->file_factory
            ->method('isFileBaseNameExists')
            ->with('filename.txt', self::RELEASE_ID, self::PROJECT_ID)
            ->willReturn(false);
        $this->file_factory
            ->method('isSameFileMarkedToBeRestored')
            ->with('filename.txt', self::RELEASE_ID)
            ->willReturn(false);

        $this->dao->method('searchFileOngoingUploadByReleaseIDNameAndExpirationDate')->willReturn([]);
        $this->dao->expects(self::once())->method('saveFileOngoingUpload')->willReturn(12);

        $document_to_upload = $this->creator->create(
            $this->release,
            $this->user,
            $current_time,
            'filename.txt',
            123
        );

        self::assertSame('/uploads/frs/file/12', $document_to_upload->getUploadHref());
    }

    public function testANewItemIsNotCreatedIfAFileWithSameNameExists(): void
    {
        $current_time = new DateTimeImmutable();

        $this->file_factory
            ->method('isFileBaseNameExists')
            ->with('filename.txt', self::RELEASE_ID, self::PROJECT_ID)
            ->willReturn(true);
        $this->file_factory
            ->method('isSameFileMarkedToBeRestored')
            ->with('filename.txt', self::RELEASE_ID)
            ->willReturn(false);

        $this->dao->expects(self::never())->method('saveFileOngoingUpload');

        $this->expectException(UploadFileNameAlreadyExistsException::class);

        $this->creator->create(
            $this->release,
            $this->user,
            $current_time,
            'filename.txt',
            123
        );
    }

    public function testANewItemIsNotCreatedIfAFileWithSameNameExistsIsMarkedAsRestored(): void
    {
        $current_time = new DateTimeImmutable();

        $this->file_factory
            ->method('isFileBaseNameExists')
            ->with('filename.txt', self::RELEASE_ID, self::PROJECT_ID)
            ->willReturn(false);
        $this->file_factory
            ->method('isSameFileMarkedToBeRestored')
            ->with('filename.txt', self::RELEASE_ID)
            ->willReturn(true);

        $this->dao->expects(self::never())->method('saveFileOngoingUpload');

        $this->expectException(UploadFileMarkedToBeRestoredException::class);

        $this->creator->create(
            $this->release,
            $this->user,
            $current_time,
            'filename.txt',
            123
        );
    }

    public function testANewItemIsNotCreatedIfAnUploadIsOngoingWithTheSameFile(): void
    {
        $current_time = new DateTimeImmutable();

        $this->file_factory
            ->method('isFileBaseNameExists')
            ->with('filename.txt', self::RELEASE_ID, self::PROJECT_ID)
            ->willReturn(false);
        $this->file_factory
            ->method('isSameFileMarkedToBeRestored')
            ->with('filename.txt', self::RELEASE_ID)
            ->willReturn(false);

        $this->dao->method('searchFileOngoingUploadByReleaseIDNameAndExpirationDate')->willReturn(
            [
                ['id' => 12, 'user_id' => 102, 'file_size' => 123],
            ]
        );

        $document_to_upload = $this->creator->create(
            $this->release,
            $this->user,
            $current_time,
            'filename.txt',
            123
        );

        self::assertSame('/uploads/frs/file/12', $document_to_upload->getUploadHref());
    }

    public function testANewItemIsNotCreatedIfManyUploadsAreOngoingWithTheSameFileWhichShouldNeverHappen(): void
    {
        $current_time = new DateTimeImmutable();

        $this->file_factory
            ->method('isFileBaseNameExists')
            ->with('filename.txt', self::RELEASE_ID, self::PROJECT_ID)
            ->willReturn(false);
        $this->file_factory
            ->method('isSameFileMarkedToBeRestored')
            ->with('filename.txt', self::RELEASE_ID)
            ->willReturn(false);

        $this->dao->method('searchFileOngoingUploadByReleaseIDNameAndExpirationDate')->willReturn(
            [
                ['id' => 12, 'user_id' => 102, 'file_size' => 123],
                ['id' => 13, 'user_id' => 102, 'file_size' => 123],
            ]
        );

        $this->dao->expects(self::never())->method('saveFileOngoingUpload');

        $this->expectException(LogicException::class);

        $this->creator->create(
            $this->release,
            $this->user,
            $current_time,
            'filename.txt',
            123
        );
    }

    public function testCreationIsRejectedWhenAnotherUserIsCreatingTheDocument(): void
    {
        $current_time = new DateTimeImmutable();

        $this->file_factory
            ->method('isFileBaseNameExists')
            ->with('filename.txt', self::RELEASE_ID, self::PROJECT_ID)
            ->willReturn(false);
        $this->file_factory
            ->method('isSameFileMarkedToBeRestored')
            ->with('filename.txt', self::RELEASE_ID)
            ->willReturn(false);

        $this->dao->method('searchFileOngoingUploadByReleaseIDNameAndExpirationDate')->willReturn(
            [
                ['user_id' => 103],
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

    public function testCreationIsRejectedWhenTheUserIsAlreadyCreatingTheDocumentWithAnotherFile(): void
    {
        $current_time = new DateTimeImmutable();

        $this->file_factory
            ->method('isFileBaseNameExists')
            ->with('filename.txt', self::RELEASE_ID, self::PROJECT_ID)
            ->willReturn(false);
        $this->file_factory
            ->method('isSameFileMarkedToBeRestored')
            ->with('filename.txt', self::RELEASE_ID)
            ->willReturn(false);

        $this->dao->method('searchFileOngoingUploadByReleaseIDNameAndExpirationDate')->willReturn(
            [
                ['user_id' => 102, 'file_size' => 123456],
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

    public function testCreationIsRejectedIfTheFileIsBiggerThanTheConfigurationLimit(): void
    {
        $current_time = new DateTimeImmutable();

        $this->file_factory
            ->method('isFileBaseNameExists')
            ->with('filename.txt', self::RELEASE_ID, self::PROJECT_ID)
            ->willReturn(false);
        $this->file_factory
            ->method('isSameFileMarkedToBeRestored')
            ->with('filename.txt', self::RELEASE_ID)
            ->willReturn(false);

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
