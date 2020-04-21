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

namespace Tuleap\Tracker\FormElement\Field\File;

use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PFUser;
use PHPUnit\Framework\TestCase;
use Tracker_FormElement_Field_File;
use Tuleap\Tracker\FormElement\Field\File\Upload\FileOngoingUploadDao;
use Tuleap\Tracker\FormElement\Field\File\Upload\Tus\FileBeingUploadedInformationProvider;
use Tuleap\Tus\TusFileInformation;

class FileInfoForTusUploadedFileReadyToBeAttachedProviderTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var Mockery\MockInterface|FileBeingUploadedInformationProvider
     */
    private $file_information_provider;
    /**
     * @var Mockery\MockInterface|FileOngoingUploadDao
     */
    private $ongoing_upload_dao;
    /**
     * @var Mockery\MockInterface|PFUser
     */
    private $current_user;
    /**
     * @var Mockery\MockInterface|Tracker_FormElement_Field_File
     */
    private $field;
    /**
     * @var AttachmentForTusUploadCreator
     */
    private $provider;

    public function setUp(): void
    {
        $this->current_user              = Mockery::mock(PFUser::class);
        $this->field                     = Mockery::mock(Tracker_FormElement_Field_File::class);
        $this->ongoing_upload_dao        = Mockery::mock(FileOngoingUploadDao::class);
        $this->file_information_provider = Mockery::mock(FileBeingUploadedInformationProvider::class);

        $this->provider = new FileInfoForTusUploadedFileReadyToBeAttachedProvider(
            $this->file_information_provider,
            $this->ongoing_upload_dao
        );

        $this->current_user->shouldReceive(['getId' => 101]);
    }

    public function testItReturnsNullIfUploadedFileCannotBeFound(): void
    {
        $this->file_information_provider
            ->shouldReceive('getFileInformationByIdForUser')
            ->with(42, $this->current_user)
            ->andReturn(null);

        $this->assertNull(
            $this->provider->getFileInfo(42, $this->current_user, $this->field)
        );
    }

    public function testItReturnsNullIfFileIsNotComplete(): void
    {
        $file_information = Mockery::mock(TusFileInformation::class);
        $file_information->shouldReceive(
            [
                'getLength' => 123,
                'getOffset' => 42
            ]
        );
        $this->file_information_provider
            ->shouldReceive('getFileInformationByIdForUser')
            ->with(42, $this->current_user)
            ->andReturn($file_information);

        $attachment = $this->provider->getFileInfo(42, $this->current_user, $this->field);
        $this->assertNull($attachment);
    }

    public function testItReturnsNullIfDataIsInconsistent(): void
    {
        $file_information = Mockery::mock(TusFileInformation::class);
        $file_information->shouldReceive(
            [
                'getLength' => 123,
                'getOffset' => 123,
                'getID'     => 42
            ]
        );
        $this->file_information_provider
            ->shouldReceive('getFileInformationByIdForUser')
            ->with(42, $this->current_user)
            ->andReturn($file_information);

        $this->ongoing_upload_dao->shouldReceive(['searchFileOngoingUploadById' => null]);

        $attachment = $this->provider->getFileInfo(42, $this->current_user, $this->field);
        $this->assertNull($attachment);
    }

    public function testItReturnsNullIfFieldIdForFileIsNotTheSameThanTheCurrentOne(): void
    {
        $file_information = Mockery::mock(TusFileInformation::class);
        $file_information->shouldReceive(
            [
                'getLength' => 123,
                'getOffset' => 123,
                'getID'     => 42
            ]
        );
        $this->file_information_provider
            ->shouldReceive('getFileInformationByIdForUser')
            ->with(42, $this->current_user)
            ->andReturn($file_information);

        $this->field->shouldReceive(['getId' => 1000]);
        $this->ongoing_upload_dao->shouldReceive(['searchFileOngoingUploadById' => ['field_id' => 1001]]);

        $attachment = $this->provider->getFileInfo(42, $this->current_user, $this->field);
        $this->assertNull($attachment);
    }

    public function testItReturnsAttachmentAndDeleteFromUploadingTable(): void
    {
        $file_information = Mockery::mock(TusFileInformation::class);
        $file_information->shouldReceive(
            [
                'getLength' => 123,
                'getOffset' => 123,
                'getID'     => 42
            ]
        );
        $this->file_information_provider
            ->shouldReceive('getFileInformationByIdForUser')
            ->with(42, $this->current_user)
            ->andReturn($file_information);

        $this->field->shouldReceive(['getId' => 1001]);
        $this->ongoing_upload_dao->shouldReceive('searchFileOngoingUploadById')->andReturn(
            [
                'id'           => 42,
                'field_id'     => 1001,
                'submitted_by' => 101,
                'filetype'     => 'text/plain',
                'filename'     => 'readme.mkd',
                'description'  => '',
                'filesize'     => 123
            ]
        );

        $attachment = $this->provider->getFileInfo(42, $this->current_user, $this->field);
        $this->assertEquals(42, $attachment->getId());
    }
}
