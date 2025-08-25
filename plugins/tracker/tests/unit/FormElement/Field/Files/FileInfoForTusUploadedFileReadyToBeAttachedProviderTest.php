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
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Tracker\FormElement\Field\Files\Upload\FileOngoingUploadDao;
use Tuleap\Tracker\FormElement\Field\Files\Upload\Tus\FileBeingUploadedInformationProvider;
use Tuleap\Tracker\Test\Builders\Fields\FilesFieldBuilder;
use Tuleap\Upload\FileAlreadyUploadedInformation;
use Tuleap\Upload\FileBeingUploadedInformation;

#[DisableReturnValueGenerationForTestDoubles]
final class FileInfoForTusUploadedFileReadyToBeAttachedProviderTest extends TestCase
{
    private FileBeingUploadedInformationProvider&MockObject $file_information_provider;
    private FileOngoingUploadDao&MockObject $ongoing_upload_dao;
    private PFUser $current_user;
    private FilesField $field;
    private FileInfoForTusUploadedFileReadyToBeAttachedProvider|AttachmentForTusUploadCreator $provider;

    public function setUp(): void
    {
        $this->current_user              = UserTestBuilder::buildWithId(101);
        $this->field                     = FilesFieldBuilder::aFileField(1000)->build();
        $this->ongoing_upload_dao        = $this->createMock(FileOngoingUploadDao::class);
        $this->file_information_provider = $this->createMock(FileBeingUploadedInformationProvider::class);

        $this->provider = new FileInfoForTusUploadedFileReadyToBeAttachedProvider(
            $this->file_information_provider,
            $this->ongoing_upload_dao
        );
    }

    public function testItReturnsNullIfUploadedFileCannotBeFound(): void
    {
        $this->file_information_provider->method('getFileInformationByIdForUser')->with(42, $this->current_user)->willReturn(null);

        self::assertNull($this->provider->getFileInfo(42, $this->current_user, $this->field));
    }

    public function testItReturnsNullIfFileIsNotComplete(): void
    {
        $this->file_information_provider->method('getFileInformationByIdForUser')->with(42, $this->current_user)
            ->willReturn(new FileBeingUploadedInformation(42, '', 123, 42));

        self::assertNull($this->provider->getFileInfo(42, $this->current_user, $this->field));
    }

    public function testItReturnsNullIfDataIsInconsistent(): void
    {
        $this->file_information_provider->method('getFileInformationByIdForUser')->with(42, $this->current_user)
            ->willReturn(new FileAlreadyUploadedInformation(42, '', 123));

        $this->ongoing_upload_dao->method('searchFileOngoingUploadById')->willReturn(null);

        self::assertNull($this->provider->getFileInfo(42, $this->current_user, $this->field));
    }

    public function testItReturnsNullIfFieldIdForFileIsNotTheSameThanTheCurrentOne(): void
    {
        $this->file_information_provider->method('getFileInformationByIdForUser')->with(42, $this->current_user)
            ->willReturn(new FileAlreadyUploadedInformation(42, '', 123));

        $this->ongoing_upload_dao->method('searchFileOngoingUploadById')->willReturn(['field_id' => 1001]);

        self::assertNull($this->provider->getFileInfo(42, $this->current_user, $this->field));
    }

    public function testItReturnsAttachmentAndDeleteFromUploadingTable(): void
    {
        $this->file_information_provider->method('getFileInformationByIdForUser')->with(42, $this->current_user)
            ->willReturn(new FileAlreadyUploadedInformation(42, '', 123));

        $this->field->id = 1001;
        $this->ongoing_upload_dao->method('searchFileOngoingUploadById')->willReturn([
            'id'           => 42,
            'field_id'     => 1001,
            'submitted_by' => 101,
            'filetype'     => 'text/plain',
            'filename'     => 'readme.mkd',
            'description'  => '',
            'filesize'     => 123,
        ]);

        self::assertEquals(42, $this->provider->getFileInfo(42, $this->current_user, $this->field)->getId());
    }
}
