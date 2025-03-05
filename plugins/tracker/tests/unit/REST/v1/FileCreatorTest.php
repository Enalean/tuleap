<?php
/**
 * Copyright (c) Enalean, 2022-Present. All Rights Reserved.
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

namespace Tuleap\Tracker\REST\v1;

use Luracast\Restler\RestException;
use PHPUnit\Framework\MockObject\MockObject;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\DB\DBTransactionExecutorPassthrough;
use Tuleap\Tracker\FormElement\Field\File\Upload\EmptyFileToUploadFinisher;
use Tuleap\Tracker\FormElement\Field\File\Upload\FileOngoingUploadDao;
use Tuleap\Tracker\FormElement\Field\File\Upload\FileToUploadCreator;
use Tuleap\Tracker\FormElement\Field\File\Upload\NewFileUpload;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class FileCreatorTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private const NEW_FILE_UPLOAD_ID = 147;
    private const FILE_NAME          = 'cryptonema.png';
    private const FILE_SIZE          = 667193;
    private const MAX_UPLOAD_SIZE    = 1000000;
    /**
     * @var FileOngoingUploadDao & MockObject
     */
    private $ongoing_dao;
    /**
     * @var EmptyFileToUploadFinisher & MockObject
     */
    private $empty_file_finisher;
    private FilePOSTRepresentation $post_payload;
    private int $max_size_upload;

    protected function setUp(): void
    {
        $this->ongoing_dao         = $this->createMock(FileOngoingUploadDao::class);
        $this->empty_file_finisher = $this->createMock(EmptyFileToUploadFinisher::class);

        $this->post_payload              = new FilePOSTRepresentation();
        $this->post_payload->name        = self::FILE_NAME;
        $this->post_payload->file_type   = 'image/png';
        $this->post_payload->file_size   = self::FILE_SIZE;
        $this->post_payload->description = 'privy logger';

        $this->max_size_upload = self::MAX_UPLOAD_SIZE;
    }

    private function create(): CreatedFileRepresentation
    {
        $uploader = UserTestBuilder::aUser()->withId(102)->build();

        $file_field = new \Tracker_FormElement_Field_File(
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

        $creator = new FileCreator(
            new FileToUploadCreator(
                $this->ongoing_dao,
                new DBTransactionExecutorPassthrough(),
                $this->max_size_upload
            ),
            $this->empty_file_finisher
        );
        return $creator->create($file_field, $uploader, $this->post_payload, new \DateTimeImmutable());
    }

    public function testItCreatesANewFileUpload(): void
    {
        $this->mockNoConflict();
        $this->ongoing_dao->method('saveFileOngoingUpload')->willReturn(self::NEW_FILE_UPLOAD_ID);

        $created_file = $this->create();
        self::assertSame(self::NEW_FILE_UPLOAD_ID, $created_file->id);
        self::assertSame('/uploads/tracker/file/' . self::NEW_FILE_UPLOAD_ID, $created_file->upload_href);
        self::assertSame(
            sprintf('/plugins/tracker/attachments/%d-%s', self::NEW_FILE_UPLOAD_ID, self::FILE_NAME),
            $created_file->download_href
        );
    }

    public function testWhenPayloadDoesNotHaveADescriptionItDefaultsToEmptyString(): void
    {
        unset($this->post_payload->description);

        $this->mockNoConflict();
        $this->ongoing_dao->expects(self::once())->method('saveFileOngoingUpload')->willReturnCallback(
            function (NewFileUpload $new_upload) {
                self::assertSame('', $new_upload->description);
                return self::NEW_FILE_UPLOAD_ID;
            }
        );

        $this->create();
    }

    public function testWhenFileSizeIsZeroItCreatesAnEmptyFile(): void
    {
        $this->post_payload->file_size = 0;

        $this->mockNoConflict();
        $this->ongoing_dao->method('saveFileOngoingUpload')->willReturn(self::NEW_FILE_UPLOAD_ID);

        $this->empty_file_finisher->expects(self::once())->method('createEmptyFile');

        $created_file = $this->create();
        self::assertNull($created_file->upload_href);
    }

    public function testWhenFileSizeExceedsMaxSizeItThrowsBadRequest(): void
    {
        $this->post_payload->file_size = self::MAX_UPLOAD_SIZE + 1;

        $this->mockNoConflict();

        $this->expectException(RestException::class);
        $this->expectExceptionCode(400);

        $this->create();
    }

    public function testWhenUploadCreationConflictItThrowsConflict(): void
    {
        $this->ongoing_dao->method('searchFileOngoingUploadByFieldIdNameAndExpirationDate')->willReturn([
            ['submitted_by' => 113, 'filesize' => self::FILE_SIZE],
        ]);

        $this->expectException(RestException::class);
        $this->expectExceptionCode(409);

        $this->create();
    }

    private function mockNoConflict(): void
    {
        $this->ongoing_dao->method('searchFileOngoingUploadByFieldIdNameAndExpirationDate')->willReturn([]);
    }
}
