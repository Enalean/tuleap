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

namespace Tuleap\Tracker\FormElement\Field\Files;

use PFUser;
use PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles;
use PHPUnit\Framework\MockObject\MockObject;
use Tracker_FileInfo;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Tracker\FormElement\Field\Files\Upload\FileOngoingUploadDao;
use Tuleap\Tracker\Test\Builders\Fields\FilesFieldBuilder;

#[DisableReturnValueGenerationForTestDoubles]
final class AttachmentForTusUploadCreatorTest extends TestCase
{
    private FileInfoForTusUploadedFileReadyToBeAttachedProvider&MockObject $provider;
    private FileOngoingUploadDao&MockObject $ongoing_upload_dao;
    private AttachmentCreator&MockObject $next_creator_in_chain;
    private PFUser $current_user;
    private FilesField $field;
    private AttachmentForTusUploadCreator $creator;
    private CreatedFileURLMapping $url_mapping;

    public function setUp(): void
    {
        $this->next_creator_in_chain = $this->createMock(AttachmentCreator::class);
        $this->current_user          = UserTestBuilder::buildWithId(101);
        $this->field                 = FilesFieldBuilder::aFileField(65415)->build();
        $this->ongoing_upload_dao    = $this->createMock(FileOngoingUploadDao::class);
        $this->provider              = $this->createMock(FileInfoForTusUploadedFileReadyToBeAttachedProvider::class);
        $this->url_mapping           = new CreatedFileURLMapping();

        $this->creator = new AttachmentForTusUploadCreator(
            $this->provider,
            $this->ongoing_upload_dao,
            $this->next_creator_in_chain
        );
    }

    public function testItDelegatesToNextCreatorInChainIfNotATusUpload(): void
    {
        $submitted_value_info = ['id' => 42];

        $attachment = new Tracker_FileInfo(42, $this->field, 101, '', 'filename', 10, 'text/xml');

        $this->next_creator_in_chain
            ->method('createAttachment')
            ->with($this->current_user, $this->field, $submitted_value_info, $this->url_mapping)
            ->willReturn($attachment);

        self::assertEquals(
            $attachment,
            $this->creator->createAttachment(
                $this->current_user,
                $this->field,
                $submitted_value_info,
                $this->url_mapping
            )
        );
    }

    public function testItReturnsNullIfUploadedFileCannotBeFound(): void
    {
        $submitted_value_info = ['tus-uploaded-id' => 42];

        $this->provider->method('getFileInfo')->with(42, $this->current_user, $this->field)->willReturn(null);

        self::assertNull(
            $this->creator->createAttachment(
                $this->current_user,
                $this->field,
                $submitted_value_info,
                $this->url_mapping
            )
        );
    }

    public function testItReturnsAttachmentAndDeleteFromUploadingTable(): void
    {
        $submitted_value_info = ['tus-uploaded-id' => 42];

        $file_info = new Tracker_FileInfo(42, $this->field, 101, '', 'filename', 10, 'text/xml');

        $this->provider->method('getFileInfo')->with(42, $this->current_user, $this->field)->willReturn($file_info);

        $this->ongoing_upload_dao->expects($this->once())->method('deleteUploadedFileThatIsAttached')->with(42);

        self::assertEquals(
            $file_info,
            $this->creator->createAttachment(
                $this->current_user,
                $this->field,
                $submitted_value_info,
                $this->url_mapping
            )
        );
    }
}
