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

namespace Tuleap\Tracker\FormElement\Field\Files\Upload;

use PHPUnit\Framework\MockObject\Stub;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\DB\DBTransactionExecutorPassthrough;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class FileToUploadCreatorTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private const UPLOADING_USER_ID = 102;
    private const FILE_SIZE         = 123;
    private const MAX_SIZE_UPLOAD   = 1000;

    /**
     * @var FileOngoingUploadDao & Stub
     */
    private $dao;
    private int $file_size;

    #[\Override]
    protected function setUp(): void
    {
        $this->file_size = self::FILE_SIZE;

        $this->dao = $this->createStub(FileOngoingUploadDao::class);
    }

    private function create(): FileToUpload
    {
        $uploader = UserTestBuilder::aUser()->withId(self::UPLOADING_USER_ID)->build();

        $file_field = new \Tuleap\Tracker\FormElement\Field\Files\FilesField(
            42,
            67,
            1,
            'attachments',
            'Attachments',
            'Irrelevant',
            1,
            'P',
            false,
            '',
            1
        );

        $creator = new FileToUploadCreator(
            $this->dao,
            new DBTransactionExecutorPassthrough(),
            self::MAX_SIZE_UPLOAD
        );
        return $creator->create(
            $file_field,
            $uploader,
            new \DateTimeImmutable(),
            'filename.txt',
            $this->file_size,
            'text/plain',
            'synderesis apio'
        );
    }

    public function testCreation(): void
    {
        $this->dao->method('searchFileOngoingUploadByFieldIdNameAndExpirationDate')->willReturn([]);
        $this->dao->method('saveFileOngoingUpload')->willReturn(12);

        $document_to_upload = $this->create();

        self::assertSame('/uploads/tracker/file/12', $document_to_upload->getUploadHref());
    }

    public function testANewItemIsNotCreatedIfAnUploadIsOngoingWithTheSameFile(): void
    {
        $this->dao->method('searchFileOngoingUploadByFieldIdNameAndExpirationDate')->willReturn([
            ['id' => 12, 'submitted_by' => self::UPLOADING_USER_ID, 'filesize' => self::FILE_SIZE],
        ]);

        $document_to_upload = $this->create();

        self::assertSame('/uploads/tracker/file/12', $document_to_upload->getUploadHref());
    }

    public function testCreationIsRejectedIfTheFileIsBiggerThanTheConfigurationLimit(): void
    {
        $this->file_size = self::MAX_SIZE_UPLOAD + 1;

        $this->expectException(UploadMaxSizeExceededException::class);
        $this->create();
    }

    public function testCreationIsRejectedWhenAnotherUserIsCreatingTheDocument(): void
    {
        $this->dao->method('searchFileOngoingUploadByFieldIdNameAndExpirationDate')->willReturn([
            ['submitted_by' => 103, 'filesize' => self::FILE_SIZE],
        ]);

        $this->expectException(UploadCreationConflictException::class);
        $this->create();
    }
}
