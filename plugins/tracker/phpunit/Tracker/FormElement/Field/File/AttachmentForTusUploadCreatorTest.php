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
use Tuleap\Tracker\FormElement\Field\File\Upload\FileOngoingUploadDao;

class AttachmentForTusUploadCreatorTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var Mockery\MockInterface|FileInfoForTusUploadedFileReadyToBeAttachedProvider
     */
    private $provider;
    /**
     * @var Mockery\MockInterface|FileOngoingUploadDao
     */
    private $ongoing_upload_dao;
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
        $this->next_creator_in_chain = Mockery::mock(AttachmentCreator::class);
        $this->current_user          = Mockery::mock(PFUser::class);
        $this->field                 = Mockery::mock(Tracker_FormElement_Field_File::class);
        $this->ongoing_upload_dao    = Mockery::mock(FileOngoingUploadDao::class);
        $this->provider              = Mockery::mock(FileInfoForTusUploadedFileReadyToBeAttachedProvider::class);
        $this->url_mapping           = Mockery::mock(CreatedFileURLMapping::class);

        $this->creator = new AttachmentForTusUploadCreator(
            $this->provider,
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

        $this->provider
            ->shouldReceive('getFileInfo')
            ->with(42, $this->current_user, $this->field)
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

    public function testItReturnsAttachmentAndDeleteFromUploadingTable(): void
    {
        $submitted_value_info = ['tus-uploaded-id' => 42];

        $file_info = Mockery::mock(Tracker_FileInfo::class);
        $file_info->shouldReceive(['getId' => 42]);

        $this->provider
            ->shouldReceive('getFileInfo')
            ->with(42, $this->current_user, $this->field)
            ->andReturn($file_info);

        $this->ongoing_upload_dao->shouldReceive('deleteUploadedFileThatIsAttached')->with(42)->once();

        $this->assertEquals(
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
