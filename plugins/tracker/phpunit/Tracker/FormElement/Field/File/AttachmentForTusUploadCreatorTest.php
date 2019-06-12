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
use Tracker_FileInfo;
use Tracker_FormElement_Field_File;
use Tracker_FormElementFactory;
use Tuleap\Tracker\FormElement\Field\File\Upload\FileOngoingUploadDao;
use Tuleap\Tracker\FormElement\Field\File\Upload\Tus\FileBeingUploadedInformationProvider;
use Tuleap\Tus\TusFileInformation;
use Tuleap\Upload\PathAllocator;

class AttachmentForTusUploadCreatorTest extends TestCase
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
     * @var Mockery\MockInterface|Tracker_FormElementFactory
     */
    private $form_element_factory;
    /**
     * @var Mockery\MockInterface|PathAllocator
     */
    private $path_allocator;
    /**
     * @var Mockery\MockInterface|AttachmentCreator
     */
    private $next_creator_in_chain;
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
    private $creator;
    /**
     * @var Mockery\MockInterface|CreatedFileURLMapping
     */
    private $url_mapping;

    public function setUp(): void
    {
        $this->next_creator_in_chain     = Mockery::mock(AttachmentCreator::class);
        $this->current_user              = Mockery::mock(PFUser::class);
        $this->field                     = Mockery::mock(Tracker_FormElement_Field_File::class);
        $this->ongoing_upload_dao        = Mockery::mock(FileOngoingUploadDao::class);
        $this->form_element_factory      = Mockery::mock(Tracker_FormElementFactory::class);
        $this->file_information_provider = Mockery::mock(FileBeingUploadedInformationProvider::class);
        $this->url_mapping                = Mockery::mock(CreatedFileURLMapping::class);

        $this->creator = new AttachmentForTusUploadCreator(
            $this->file_information_provider,
            $this->ongoing_upload_dao,
            $this->next_creator_in_chain
        );

        $this->current_user->shouldReceive(['getId' => 101]);
    }

    public function testItDelegatesToNextCreatorInChainIfNotATusUpload(): void
    {
        $submitted_value_info = ['id' => 42];

        $attachment = Mockery::mock(Tracker_FileInfo::class);

        $this->next_creator_in_chain
            ->shouldReceive('createAttachment')
            ->with($this->current_user, $this->field, $submitted_value_info, $this->url_mapping)
            ->andReturn($attachment);

        $this->assertEquals(
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

        $this->file_information_provider
            ->shouldReceive('getFileInformationByIdForUser')
            ->with(42, $this->current_user)
            ->andReturn(null);

        $this->assertNull(
            $this->creator->createAttachment(
                $this->current_user,
                $this->field,
                $submitted_value_info,
                $this->url_mapping
            )
        );
    }

    public function testItReturnsNullIfFileIsNotComplete(): void
    {
        $submitted_value_info = ['tus-uploaded-id' => 42];

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

        $attachment = $this->creator->createAttachment(
            $this->current_user,
            $this->field,
            $submitted_value_info,
            $this->url_mapping
        );
        $this->assertNull($attachment);
    }

    public function testItReturnsNullIfDataIsInconsistent(): void
    {
        $submitted_value_info = ['tus-uploaded-id' => 42];

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

        $attachment = $this->creator->createAttachment(
            $this->current_user,
            $this->field,
            $submitted_value_info,
            $this->url_mapping
        );
        $this->assertNull($attachment);
    }

    public function testItReturnsNullIfFieldIdForFileIsNotTheSameThanTheCurrentOne(): void
    {
        $submitted_value_info = ['tus-uploaded-id' => 42];

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

        $attachment = $this->creator->createAttachment(
            $this->current_user,
            $this->field,
            $submitted_value_info,
            $this->url_mapping
        );
        $this->assertNull($attachment);
    }

    public function testItReturnsAttachmentAndDeleteFromUploadingTable(): void
    {
        $submitted_value_info = ['tus-uploaded-id' => 42];

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
        $this->ongoing_upload_dao->shouldReceive('deleteUploadedFileThatIsAttached')->with(42)->once();

        $attachment = $this->creator->createAttachment(
            $this->current_user,
            $this->field,
            $submitted_value_info,
            $this->url_mapping
        );
        $this->assertEquals(42, $attachment->getId());
    }
}
