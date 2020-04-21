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
use PFUser;
use PHPUnit\Framework\TestCase;
use Tracker_Artifact_Attachment_TemporaryFile;
use Tracker_Artifact_Attachment_TemporaryFileManager;
use Tracker_FormElement_Field_File;

class AttachmentForRestCreatorTest extends TestCase
{
    use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

    public function testCreateAttachment(): void
    {
        $rule_file = Mockery::mock(\Rule_File::class);
        $rule_file->shouldReceive('isValid')->andReturn(true);

        $current_user = Mockery::mock(PFUser::class);
        $current_user->shouldReceive('getId')->andReturn(101);

        $mover = Mockery::mock(AttachmentToFinalPlaceMover::class);
        $mover
            ->shouldReceive('moveAttachmentToFinalPlace')
            ->with(Mockery::any(), 'rename', '/var/tmp')
            ->andReturn(true);

        $submitted_value_info = ['id' => 42];

        $field = Mockery::mock(Tracker_FormElement_Field_File::class);

        $temporary_file = Mockery::mock(Tracker_Artifact_Attachment_TemporaryFile::class);
        $temporary_file->shouldReceive(
            [
                'getId'          => 42,
                'getDescription' => '',
                'getName'        => 'readme.mkd',
                'getSize'        => 123,
                'getType'        => 'text/plain'
            ]
        );

        $temporary_file_manager = Mockery::mock(Tracker_Artifact_Attachment_TemporaryFileManager::class);
        $temporary_file_manager
            ->shouldReceive('getFileByTemporaryName')
            ->with(42)
            ->andReturn($temporary_file);
        $temporary_file_manager
            ->shouldReceive('exists')
            ->with($current_user, 42)
            ->andReturn(true);
        $temporary_file_manager
            ->shouldReceive('getPath')
            ->with($current_user, 42)
            ->andReturn('/var/tmp');
        $temporary_file_manager
            ->shouldReceive('removeTemporaryFileInDBByTemporaryName')
            ->with(42);

        $next_creator_in_chain = Mockery::mock(AttachmentCreator::class);

        $creator = Mockery::mock(
            AttachmentForRestCreator::class . '[delete]',
            [
                $mover,
                $temporary_file_manager,
                $next_creator_in_chain,
                $rule_file
            ]
        );
        \assert($creator instanceof AttachmentForRestCreator || $creator instanceof Mockery\MockInterface);
        $creator->shouldAllowMockingProtectedMethods();

        $creator->shouldReceive('delete')->never();

        $url_mapping = Mockery::mock(CreatedFileURLMapping::class);
        $url_mapping->shouldReceive('add')->never();

        $attachment = $creator->createAttachment($current_user, $field, $submitted_value_info, $url_mapping);
        $this->assertEquals('readme.mkd', $attachment->getFilename());
    }

    public function testItReturnsNullIfMoveToFinalPlaceIsNotPossible(): void
    {
        $rule_file = Mockery::mock(\Rule_File::class);
        $rule_file->shouldReceive('isValid')->andReturn(true);

        $current_user = Mockery::mock(PFUser::class);
        $current_user->shouldReceive('getId')->andReturn(101);

        $mover = Mockery::mock(AttachmentToFinalPlaceMover::class);
        $mover
            ->shouldReceive('moveAttachmentToFinalPlace')
            ->with(Mockery::any(), 'rename', '/var/tmp')
            ->andReturn(false);

        $submitted_value_info = ['id' => 42];

        $field = Mockery::mock(Tracker_FormElement_Field_File::class);

        $temporary_file = Mockery::mock(Tracker_Artifact_Attachment_TemporaryFile::class);
        $temporary_file->shouldReceive(
            [
                'getId'          => 42,
                'getDescription' => '',
                'getName'        => 'readme.mkd',
                'getSize'        => 123,
                'getType'        => 'text/plain'
            ]
        );

        $temporary_file_manager = Mockery::mock(Tracker_Artifact_Attachment_TemporaryFileManager::class);
        $temporary_file_manager
            ->shouldReceive('getFileByTemporaryName')
            ->with(42)
            ->andReturn($temporary_file);
        $temporary_file_manager
            ->shouldReceive('exists')
            ->with($current_user, 42)
            ->andReturn(true);
        $temporary_file_manager
            ->shouldReceive('getPath')
            ->with($current_user, 42)
            ->andReturn('/var/tmp');
        $temporary_file_manager
            ->shouldReceive('removeTemporaryFileInDBByTemporaryName')
            ->with(42);

        $next_creator_in_chain = Mockery::mock(AttachmentCreator::class);

        $creator = Mockery::mock(
            AttachmentForRestCreator::class . '[delete]',
            [
                $mover,
                $temporary_file_manager,
                $next_creator_in_chain,
                $rule_file
            ]
        );
        \assert($creator instanceof AttachmentForRestCreator || $creator instanceof Mockery\MockInterface);
        $creator->shouldAllowMockingProtectedMethods();

        $creator->shouldReceive('delete')->never();

        $url_mapping = Mockery::mock(CreatedFileURLMapping::class);
        $url_mapping->shouldReceive('add')->never();

        $attachment = $creator->createAttachment($current_user, $field, $submitted_value_info, $url_mapping);
        $this->assertNull($attachment);
    }

    public function testItReturnsNullIfTemporaryFileDoesNotExist(): void
    {
        $rule_file = Mockery::mock(\Rule_File::class);
        $rule_file->shouldReceive('isValid')->andReturn(true);

        $current_user = Mockery::mock(PFUser::class);
        $current_user->shouldReceive('getId')->andReturn(101);

        $mover = Mockery::mock(AttachmentToFinalPlaceMover::class);

        $submitted_value_info = ['id' => 42];

        $field = Mockery::mock(Tracker_FormElement_Field_File::class);

        $temporary_file = Mockery::mock(Tracker_Artifact_Attachment_TemporaryFile::class);
        $temporary_file->shouldReceive(
            [
                'getId'          => 42,
                'getDescription' => '',
                'getName'        => 'readme.mkd',
                'getSize'        => 123,
                'getType'        => 'text/plain'
            ]
        );

        $temporary_file_manager = Mockery::mock(Tracker_Artifact_Attachment_TemporaryFileManager::class);
        $temporary_file_manager
            ->shouldReceive('getFileByTemporaryName')
            ->with(42)
            ->andReturn($temporary_file);
        $temporary_file_manager
            ->shouldReceive('exists')
            ->with($current_user, 42)
            ->andReturn(false);
        $temporary_file_manager
            ->shouldReceive('getPath')
            ->with($current_user, 42)
            ->andReturn('/var/tmp');
        $temporary_file_manager
            ->shouldReceive('removeTemporaryFileInDBByTemporaryName')
            ->with(42);

        $next_creator_in_chain = Mockery::mock(AttachmentCreator::class);

        $creator = Mockery::mock(
            AttachmentForRestCreator::class . '[delete]',
            [
                $mover,
                $temporary_file_manager,
                $next_creator_in_chain,
                $rule_file
            ]
        );
        \assert($creator instanceof AttachmentForRestCreator || $creator instanceof Mockery\MockInterface);
        $creator->shouldAllowMockingProtectedMethods();

        $creator->shouldReceive('delete')->once();

        $url_mapping = Mockery::mock(CreatedFileURLMapping::class);
        $url_mapping->shouldReceive('add')->never();

        $attachment = $creator->createAttachment($current_user, $field, $submitted_value_info, $url_mapping);
        $this->assertNull($attachment);
    }

    public function testItDelegatesToNextCreatorInChainIfThereIsNoTemporaryFileForGivenId(): void
    {
        $rule_file = Mockery::mock(\Rule_File::class);
        $rule_file->shouldReceive('isValid')->andReturn(true);

        $current_user = Mockery::mock(PFUser::class);
        $current_user->shouldReceive('getId')->andReturn(101);

        $mover = Mockery::mock(AttachmentToFinalPlaceMover::class);

        $submitted_value_info = ['id' => 42];

        $field = Mockery::mock(Tracker_FormElement_Field_File::class);

        $url_mapping = Mockery::mock(CreatedFileURLMapping::class);

        $temporary_file_manager = Mockery::mock(Tracker_Artifact_Attachment_TemporaryFileManager::class);
        $temporary_file_manager
            ->shouldReceive('getFileByTemporaryName')
            ->with(42)
            ->andReturn(null);

        $next_creator_in_chain = Mockery::mock(AttachmentCreator::class);
        $next_creator_in_chain
            ->shouldReceive('createAttachment')
            ->with($current_user, $field, $submitted_value_info, $url_mapping);

        $creator = Mockery::mock(
            AttachmentForRestCreator::class . '[delete]',
            [
                $mover,
                $temporary_file_manager,
                $next_creator_in_chain,
                $rule_file
            ]
        );
        \assert($creator instanceof AttachmentForRestCreator || $creator instanceof Mockery\MockInterface);
        $creator->shouldAllowMockingProtectedMethods();

        $creator->shouldReceive('delete')->never();

        $url_mapping->shouldReceive('add')->never();

        $attachment = $creator->createAttachment($current_user, $field, $submitted_value_info, $url_mapping);
        $this->assertNull($attachment);
    }

    public function testItDelegatesToNextCreatorInChainIfThereIsNoIdEntryInSubmittedValueInfo(): void
    {
        $rule_file = Mockery::mock(\Rule_File::class);
        $rule_file->shouldReceive('isValid')->andReturn(true);

        $current_user = Mockery::mock(PFUser::class);
        $current_user->shouldReceive('getId')->andReturn(101);

        $mover = Mockery::mock(AttachmentToFinalPlaceMover::class);

        $submitted_value_info = [];

        $field = Mockery::mock(Tracker_FormElement_Field_File::class);

        $temporary_file_manager = Mockery::mock(Tracker_Artifact_Attachment_TemporaryFileManager::class);

        $url_mapping = Mockery::mock(CreatedFileURLMapping::class);

        $next_creator_in_chain = Mockery::mock(AttachmentCreator::class);
        $next_creator_in_chain
            ->shouldReceive('createAttachment')
            ->with($current_user, $field, $submitted_value_info, $url_mapping);

        $creator = Mockery::mock(
            AttachmentForRestCreator::class . '[delete]',
            [
                $mover,
                $temporary_file_manager,
                $next_creator_in_chain,
                $rule_file
            ]
        );
        \assert($creator instanceof AttachmentForRestCreator || $creator instanceof Mockery\MockInterface);
        $creator->shouldAllowMockingProtectedMethods();

        $creator->shouldReceive('delete')->never();

        $url_mapping->shouldReceive('add')->never();

        $attachment = $creator->createAttachment($current_user, $field, $submitted_value_info, $url_mapping);
        $this->assertNull($attachment);
    }

    public function testItDelegatesToNextCreatorInChainIfTheFileIsNotValid(): void
    {
        $rule_file = Mockery::mock(\Rule_File::class);
        $rule_file->shouldReceive('isValid')->andReturn(false);

        $current_user = Mockery::mock(PFUser::class);
        $current_user->shouldReceive('getId')->andReturn(101);

        $mover = Mockery::mock(AttachmentToFinalPlaceMover::class);

        $submitted_value_info = ['id' => 42];

        $field = Mockery::mock(Tracker_FormElement_Field_File::class);

        $temporary_file_manager = Mockery::mock(Tracker_Artifact_Attachment_TemporaryFileManager::class);

        $url_mapping = Mockery::mock(CreatedFileURLMapping::class);

        $next_creator_in_chain = Mockery::mock(AttachmentCreator::class);
        $next_creator_in_chain
            ->shouldReceive('createAttachment')
            ->with($current_user, $field, $submitted_value_info, $url_mapping);

        $creator = Mockery::mock(
            AttachmentForRestCreator::class . '[delete]',
            [
                $mover,
                $temporary_file_manager,
                $next_creator_in_chain,
                $rule_file
            ]
        );
        \assert($creator instanceof AttachmentForRestCreator || $creator instanceof Mockery\MockInterface);
        $creator->shouldAllowMockingProtectedMethods();

        $creator->shouldReceive('delete')->never();

        $url_mapping->shouldReceive('add')->never();

        $attachment = $creator->createAttachment($current_user, $field, $submitted_value_info, $url_mapping);
        $this->assertNull($attachment);
    }
}
