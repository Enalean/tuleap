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
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Tracker\Test\Builders\Fields\FileFieldBuilder;

final class AttachmentForTraditionalUploadCreatorTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

    public function testItCreatesAttachment(): void
    {
        $rule_file = Mockery::mock(\Rule_File::class);
        $rule_file->shouldReceive('isValid')->andReturn(true);

        $current_user = UserTestBuilder::aUser()->withId(101)->build();

        $mover = Mockery::mock(AttachmentToFinalPlaceMover::class);
        $mover
            ->shouldReceive('moveAttachmentToFinalPlace')
            ->with(Mockery::any(), 'move_uploaded_file', '/var/tmp')
            ->andReturn(true);

        $submitted_value_info = [
            'description' => '',
            'name'        => 'readme.mkd',
            'size'        => 123,
            'type'        => 'text/plain',
            'tmp_name'    => '/var/tmp',
        ];

        $field = FileFieldBuilder::aFileField(1234)->build();

        $creator = \Mockery::mock(AttachmentForTraditionalUploadCreator::class . '[save]', [$mover, $rule_file]);
        \assert($creator instanceof AttachmentForTraditionalUploadCreator || $creator instanceof \Mockery\MockInterface);
        $creator->shouldAllowMockingProtectedMethods();

        $creator->shouldReceive('save')->andReturn(true);

        $url_mapping = Mockery::mock(CreatedFileURLMapping::class);
        $url_mapping->shouldReceive('add')->never();

        $attachment = $creator->createAttachment($current_user, $field, $submitted_value_info, $url_mapping);
        $this->assertEquals(101, $attachment->getSubmittedBy());
    }

    public function testItCreatesAttachmentWithCopyInCaseOfArtifactImport(): void
    {
        $rule_file = Mockery::mock(\Rule_File::class);
        $rule_file->shouldReceive('isValid')->andReturn(true);

        $current_user = UserTestBuilder::aUser()->withId(101)->build();

        $mover = Mockery::mock(AttachmentToFinalPlaceMover::class);
        $mover
            ->shouldReceive('moveAttachmentToFinalPlace')
            ->with(Mockery::any(), 'copy', '/var/tmp')
            ->andReturn(true);

        $submitted_value_info = [
            'description' => '',
            'name'        => 'readme.mkd',
            'size'        => 123,
            'type'        => 'text/plain',
            'tmp_name'    => '/var/tmp',
            'is_migrated' => true,
        ];

        $field = FileFieldBuilder::aFileField(1234)->build();

        $creator = \Mockery::mock(AttachmentForTraditionalUploadCreator::class . '[save]', [$mover, $rule_file]);
        \assert($creator instanceof AttachmentForTraditionalUploadCreator || $creator instanceof \Mockery\MockInterface);
        $creator->shouldAllowMockingProtectedMethods();

        $creator->shouldReceive('save')->andReturn(true);

        $url_mapping = Mockery::mock(CreatedFileURLMapping::class);
        $url_mapping->shouldReceive('add')->never();

        $attachment = $creator->createAttachment($current_user, $field, $submitted_value_info, $url_mapping);
        $this->assertEquals(101, $attachment->getSubmittedBy());
    }

    public function testItStoreMappingBetweenXMIdAndFileInfoId(): void
    {
        $rule_file = Mockery::mock(\Rule_File::class);
        $rule_file->shouldReceive('isValid')->andReturn(true);

        $current_user = UserTestBuilder::aUser()->withId(101)->build();

        $mover = Mockery::mock(AttachmentToFinalPlaceMover::class);
        $mover
            ->shouldReceive('moveAttachmentToFinalPlace')
            ->with(Mockery::any(), 'copy', '/var/tmp')
            ->andReturn(true);

        $submitted_value_info = [
            'description'          => '',
            'name'                 => 'readme.mkd',
            'size'                 => 42,
            'type'                 => 'text/plain',
            'tmp_name'             => '/var/tmp',
            'is_migrated'          => true,
            'previous_fileinfo_id' => 123,
        ];

        $field = FileFieldBuilder::aFileField(1234)->build();

        $creator = \Mockery::mock(AttachmentForTraditionalUploadCreator::class . '[save]', [$mover, $rule_file]);
        \assert($creator instanceof AttachmentForTraditionalUploadCreator || $creator instanceof \Mockery\MockInterface);
        $creator->shouldAllowMockingProtectedMethods();

        $creator->shouldReceive('save')->andReturn(true);

        $url_mapping = Mockery::mock(CreatedFileURLMapping::class);

        // When Tracker_FileInfo saves itself, it updates its id (initially set to 0) to the new id.
        // Since the Tracker_FileInfo instance is created in the method under test, there is no mean
        // to know the new id, therefore we trust Tracker_FileInfo code and test with default id 0.
        $url_mapping
            ->shouldReceive('add')
            ->with('/plugins/tracker/attachments/123-readme.mkd', '/plugins/tracker/attachments/0-readme.mkd')
            ->once();

        $attachment = $creator->createAttachment($current_user, $field, $submitted_value_info, $url_mapping);
        $this->assertEquals(101, $attachment->getSubmittedBy());
    }

    public function testItUsesSubmittedByFromValueInfoInsteadOfCurrentUser(): void
    {
        $rule_file = Mockery::mock(\Rule_File::class);
        $rule_file->shouldReceive('isValid')->andReturn(true);

        $current_user = UserTestBuilder::aUser()->withId(101)->build();

        $another_user = UserTestBuilder::aUser()->withId(666)->build();

        $mover = Mockery::mock(AttachmentToFinalPlaceMover::class);
        $mover
            ->shouldReceive('moveAttachmentToFinalPlace')
            ->with(Mockery::any(), 'move_uploaded_file', '/var/tmp')
            ->andReturn(true);

        $submitted_value_info = [
            'description'  => '',
            'name'         => 'readme.mkd',
            'size'         => 123,
            'type'         => 'text/plain',
            'tmp_name'     => '/var/tmp',
            'submitted_by' => $another_user,
        ];

        $field = FileFieldBuilder::aFileField(1234)->build();

        $creator = \Mockery::mock(AttachmentForTraditionalUploadCreator::class . '[save]', [$mover, $rule_file]);
        \assert($creator instanceof AttachmentForTraditionalUploadCreator || $creator instanceof \Mockery\MockInterface);
        $creator->shouldAllowMockingProtectedMethods();

        $creator->shouldReceive('save')->andReturn(true);

        $url_mapping = Mockery::mock(CreatedFileURLMapping::class);
        $url_mapping->shouldReceive('add')->never();

        $attachment = $creator->createAttachment($current_user, $field, $submitted_value_info, $url_mapping);
        $this->assertEquals(666, $attachment->getSubmittedBy());
    }

    public function testItReturnsNullIfAttachmentCannotBeSavedInDb(): void
    {
        $rule_file = Mockery::mock(\Rule_File::class);
        $rule_file->shouldReceive('isValid')->andReturn(true);

        $current_user = UserTestBuilder::aUser()->withId(101)->build();

        $mover = Mockery::mock(AttachmentToFinalPlaceMover::class);
        $mover
            ->shouldReceive('moveAttachmentToFinalPlace')
            ->with(Mockery::any(), 'move_uploaded_file', '/var/tmp')
            ->andReturn(true);

        $submitted_value_info = [
            'description' => '',
            'name'        => 'readme.mkd',
            'size'        => 123,
            'type'        => 'text/plain',
            'tmp_name'    => '/var/tmp',
        ];

        $field = FileFieldBuilder::aFileField(1234)->build();

        $creator = \Mockery::mock(AttachmentForTraditionalUploadCreator::class . '[save]', [$mover, $rule_file]);
        \assert($creator instanceof AttachmentForTraditionalUploadCreator || $creator instanceof \Mockery\MockInterface);
        $creator->shouldAllowMockingProtectedMethods();

        $creator->shouldReceive('save')->andReturn(false);

        $url_mapping = Mockery::mock(CreatedFileURLMapping::class);
        $url_mapping->shouldReceive('add')->never();

        $attachment = $creator->createAttachment($current_user, $field, $submitted_value_info, $url_mapping);
        $this->assertNull($attachment);
    }

    public function testItReturnsNullIfAttachmentCannotBeMovedToFinalPlace(): void
    {
        $rule_file = Mockery::mock(\Rule_File::class);
        $rule_file->shouldReceive('isValid')->andReturn(true);

        $current_user = UserTestBuilder::aUser()->withId(101)->build();

        $mover = Mockery::mock(AttachmentToFinalPlaceMover::class);
        $mover
            ->shouldReceive('moveAttachmentToFinalPlace')
            ->with(Mockery::any(), 'move_uploaded_file', '/var/tmp')
            ->andReturn(false);

        $submitted_value_info = [
            'description' => '',
            'name'        => 'readme.mkd',
            'size'        => 123,
            'type'        => 'text/plain',
            'tmp_name'    => '/var/tmp',
        ];

        $field = FileFieldBuilder::aFileField(1234)->build();

        $creator = \Mockery::mock(AttachmentForTraditionalUploadCreator::class . '[save]', [$mover, $rule_file]);
        \assert($creator instanceof AttachmentForTraditionalUploadCreator || $creator instanceof \Mockery\MockInterface);
        $creator->shouldAllowMockingProtectedMethods();

        $creator->shouldReceive('save')->andReturn(true);

        $url_mapping = Mockery::mock(CreatedFileURLMapping::class);
        $url_mapping->shouldReceive('add')->never();

        $attachment = $creator->createAttachment($current_user, $field, $submitted_value_info, $url_mapping);
        $this->assertNull($attachment);
    }

    public function testItReturnsNullIfFileIsNotValid(): void
    {
        $rule_file = Mockery::mock(\Rule_File::class);
        $rule_file->shouldReceive('isValid')->andReturn(false);

        $current_user = UserTestBuilder::aUser()->build();

        $mover                = Mockery::mock(AttachmentToFinalPlaceMover::class);
        $submitted_value_info = [];

        $field = FileFieldBuilder::aFileField(1234)->build();

        $creator = \Mockery::mock(AttachmentForTraditionalUploadCreator::class . '[save]', [$mover, $rule_file]);
        \assert($creator instanceof AttachmentForTraditionalUploadCreator || $creator instanceof \Mockery\MockInterface);
        $creator->shouldAllowMockingProtectedMethods();

        $url_mapping = Mockery::mock(CreatedFileURLMapping::class);
        $url_mapping->shouldReceive('add')->never();

        $attachment = $creator->createAttachment($current_user, $field, $submitted_value_info, $url_mapping);
        $this->assertNull($attachment);
    }

    public function testItCreatesAttachmentWithMove(): void
    {
        $rule_file = Mockery::mock(\Rule_File::class);
        $rule_file->shouldReceive('isValid')->andReturn(true);

        $current_user = UserTestBuilder::aUser()->withId(101)->build();

        $mover = Mockery::mock(AttachmentToFinalPlaceMover::class);
        $mover
            ->shouldReceive('moveAttachmentToFinalPlace')
            ->with(Mockery::any(), 'rename', '/var/tmp')
            ->andReturn(true);

        $submitted_value_info = [
            'description' => '',
            'name' => 'readme.mkd',
            'size' => 123,
            'type' => 'text/plain',
            'tmp_name' => '/var/tmp',
            'is_moved' => true,
        ];

        $field = FileFieldBuilder::aFileField(1234)->build();

        $creator = \Mockery::mock(AttachmentForTraditionalUploadCreator::class . '[save]', [$mover, $rule_file]);
        \assert($creator instanceof AttachmentForTraditionalUploadCreator || $creator instanceof \Mockery\MockInterface);
        $creator->shouldAllowMockingProtectedMethods();

        $creator->shouldReceive('save')->andReturn(true);

        $url_mapping = Mockery::mock(CreatedFileURLMapping::class);
        $url_mapping->shouldReceive('add')->never();

        $attachment = $creator->createAttachment($current_user, $field, $submitted_value_info, $url_mapping);
        $this->assertEquals(101, $attachment->getSubmittedBy());
    }
}
