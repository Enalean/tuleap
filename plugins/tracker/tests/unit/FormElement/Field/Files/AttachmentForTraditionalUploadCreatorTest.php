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

use PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles;
use Rule_File;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Tracker\Test\Builders\Fields\FilesFieldBuilder;

#[DisableReturnValueGenerationForTestDoubles]
final class AttachmentForTraditionalUploadCreatorTest extends TestCase
{
    public function testItCreatesAttachment(): void
    {
        $rule_file = $this->createMock(Rule_File::class);
        $rule_file->method('isValid')->willReturn(true);

        $current_user = UserTestBuilder::aUser()->withId(101)->build();

        $mover = $this->createMock(AttachmentToFinalPlaceMover::class);
        $mover->method('moveAttachmentToFinalPlace')->with(self::anything(), 'move_uploaded_file', '/var/tmp')->willReturn(true);

        $submitted_value_info = [
            'description' => '',
            'name'        => 'readme.mkd',
            'size'        => 123,
            'type'        => 'text/plain',
            'tmp_name'    => '/var/tmp',
        ];

        $field = FilesFieldBuilder::aFileField(1234)->build();

        $creator = $this->getMockBuilder(AttachmentForTraditionalUploadCreator::class)
            ->setConstructorArgs([$mover, $rule_file])
            ->onlyMethods(['save'])
            ->getMock();

        $creator->method('save')->willReturn(true);

        $url_mapping = $this->createMock(CreatedFileURLMapping::class);
        $url_mapping->expects($this->never())->method('add');

        $attachment = $creator->createAttachment($current_user, $field, $submitted_value_info, $url_mapping);
        self::assertEquals(101, $attachment->getSubmittedBy());
    }

    public function testItCreatesAttachmentWithCopyInCaseOfArtifactImport(): void
    {
        $rule_file = $this->createMock(Rule_File::class);
        $rule_file->method('isValid')->willReturn(true);

        $current_user = UserTestBuilder::aUser()->withId(101)->build();

        $mover = $this->createMock(AttachmentToFinalPlaceMover::class);
        $mover->method('moveAttachmentToFinalPlace')->with(self::anything(), 'copy', '/var/tmp')->willReturn(true);

        $submitted_value_info = [
            'description' => '',
            'name'        => 'readme.mkd',
            'size'        => 123,
            'type'        => 'text/plain',
            'tmp_name'    => '/var/tmp',
            'is_migrated' => true,
        ];

        $field = FilesFieldBuilder::aFileField(1234)->build();

        $creator = $this->getMockBuilder(AttachmentForTraditionalUploadCreator::class)
            ->setConstructorArgs([$mover, $rule_file])
            ->onlyMethods(['save'])
            ->getMock();

        $creator->method('save')->willReturn(true);

        $url_mapping = $this->createMock(CreatedFileURLMapping::class);
        $url_mapping->expects($this->never())->method('add');

        $attachment = $creator->createAttachment($current_user, $field, $submitted_value_info, $url_mapping);
        self::assertEquals(101, $attachment->getSubmittedBy());
    }

    public function testItStoreMappingBetweenXMIdAndFileInfoId(): void
    {
        $rule_file = $this->createMock(Rule_File::class);
        $rule_file->method('isValid')->willReturn(true);

        $current_user = UserTestBuilder::aUser()->withId(101)->build();

        $mover = $this->createMock(AttachmentToFinalPlaceMover::class);
        $mover->method('moveAttachmentToFinalPlace')->with(self::anything(), 'copy', '/var/tmp')->willReturn(true);

        $submitted_value_info = [
            'description'          => '',
            'name'                 => 'readme.mkd',
            'size'                 => 42,
            'type'                 => 'text/plain',
            'tmp_name'             => '/var/tmp',
            'is_migrated'          => true,
            'previous_fileinfo_id' => 123,
        ];

        $field = FilesFieldBuilder::aFileField(1234)->build();

        $creator = $this->getMockBuilder(AttachmentForTraditionalUploadCreator::class)
            ->setConstructorArgs([$mover, $rule_file])
            ->onlyMethods(['save'])
            ->getMock();

        $creator->method('save')->willReturn(true);

        $url_mapping = $this->createMock(CreatedFileURLMapping::class);

        // When Tracker_FileInfo saves itself, it updates its id (initially set to 0) to the new id.
        // Since the Tracker_FileInfo instance is created in the method under test, there is no mean
        // to know the new id, therefore we trust Tracker_FileInfo code and test with default id 0.
        $url_mapping->expects($this->once())->method('add')
            ->with('/plugins/tracker/attachments/123-readme.mkd', '/plugins/tracker/attachments/0-readme.mkd');

        $attachment = $creator->createAttachment($current_user, $field, $submitted_value_info, $url_mapping);
        self::assertEquals(101, $attachment->getSubmittedBy());
    }

    public function testItUsesSubmittedByFromValueInfoInsteadOfCurrentUser(): void
    {
        $rule_file = $this->createMock(Rule_File::class);
        $rule_file->method('isValid')->willReturn(true);

        $current_user = UserTestBuilder::aUser()->withId(101)->build();

        $another_user = UserTestBuilder::aUser()->withId(666)->build();

        $mover = $this->createMock(AttachmentToFinalPlaceMover::class);
        $mover->method('moveAttachmentToFinalPlace')->with(self::anything(), 'move_uploaded_file', '/var/tmp')->willReturn(true);

        $submitted_value_info = [
            'description'  => '',
            'name'         => 'readme.mkd',
            'size'         => 123,
            'type'         => 'text/plain',
            'tmp_name'     => '/var/tmp',
            'submitted_by' => $another_user,
        ];

        $field = FilesFieldBuilder::aFileField(1234)->build();

        $creator = $this->getMockBuilder(AttachmentForTraditionalUploadCreator::class)
            ->setConstructorArgs([$mover, $rule_file])
            ->onlyMethods(['save'])
            ->getMock();

        $creator->method('save')->willReturn(true);

        $url_mapping = $this->createMock(CreatedFileURLMapping::class);
        $url_mapping->expects($this->never())->method('add');

        $attachment = $creator->createAttachment($current_user, $field, $submitted_value_info, $url_mapping);
        self::assertEquals(666, $attachment->getSubmittedBy());
    }

    public function testItReturnsNullIfAttachmentCannotBeSavedInDb(): void
    {
        $rule_file = $this->createMock(Rule_File::class);
        $rule_file->method('isValid')->willReturn(true);

        $current_user = UserTestBuilder::aUser()->withId(101)->build();

        $mover = $this->createMock(AttachmentToFinalPlaceMover::class);
        $mover->method('moveAttachmentToFinalPlace')->with(self::anything(), 'move_uploaded_file', '/var/tmp')->willReturn(true);

        $submitted_value_info = [
            'description' => '',
            'name'        => 'readme.mkd',
            'size'        => 123,
            'type'        => 'text/plain',
            'tmp_name'    => '/var/tmp',
        ];

        $field = FilesFieldBuilder::aFileField(1234)->build();

        $creator = $this->getMockBuilder(AttachmentForTraditionalUploadCreator::class)
            ->setConstructorArgs([$mover, $rule_file])
            ->onlyMethods(['save'])
            ->getMock();

        $creator->method('save')->willReturn(false);

        $url_mapping = $this->createMock(CreatedFileURLMapping::class);
        $url_mapping->expects($this->never())->method('add');

        $attachment = $creator->createAttachment($current_user, $field, $submitted_value_info, $url_mapping);
        self::assertNull($attachment);
    }

    public function testItReturnsNullIfAttachmentCannotBeMovedToFinalPlace(): void
    {
        $rule_file = $this->createMock(Rule_File::class);
        $rule_file->method('isValid')->willReturn(true);

        $current_user = UserTestBuilder::aUser()->withId(101)->build();

        $mover = $this->createMock(AttachmentToFinalPlaceMover::class);
        $mover->method('moveAttachmentToFinalPlace')->with(self::anything(), 'move_uploaded_file', '/var/tmp')->willReturn(false);

        $submitted_value_info = [
            'description' => '',
            'name'        => 'readme.mkd',
            'size'        => 123,
            'type'        => 'text/plain',
            'tmp_name'    => '/var/tmp',
        ];

        $field = FilesFieldBuilder::aFileField(1234)->build();

        $creator = $this->getMockBuilder(AttachmentForTraditionalUploadCreator::class)
            ->setConstructorArgs([$mover, $rule_file])
            ->onlyMethods(['save'])
            ->getMock();

        $creator->method('save')->willReturn(true);

        $url_mapping = $this->createMock(CreatedFileURLMapping::class);
        $url_mapping->expects($this->never())->method('add');

        $attachment = $creator->createAttachment($current_user, $field, $submitted_value_info, $url_mapping);
        self::assertNull($attachment);
    }

    public function testItReturnsNullIfFileIsNotValid(): void
    {
        $rule_file = $this->createMock(Rule_File::class);
        $rule_file->method('isValid')->willReturn(false);

        $current_user = UserTestBuilder::aUser()->build();

        $submitted_value_info = [];

        $field = FilesFieldBuilder::aFileField(1234)->build();

        $creator = $this->getMockBuilder(AttachmentForTraditionalUploadCreator::class)
            ->setConstructorArgs([new AttachmentToFinalPlaceMover(), $rule_file])
            ->onlyMethods(['save'])
            ->getMock();

        $url_mapping = $this->createMock(CreatedFileURLMapping::class);
        $url_mapping->expects($this->never())->method('add');

        $attachment = $creator->createAttachment($current_user, $field, $submitted_value_info, $url_mapping);
        self::assertNull($attachment);
    }

    public function testItCreatesAttachmentWithMove(): void
    {
        $rule_file = $this->createMock(Rule_File::class);
        $rule_file->method('isValid')->willReturn(true);

        $current_user = UserTestBuilder::aUser()->withId(101)->build();

        $mover = $this->createMock(AttachmentToFinalPlaceMover::class);
        $mover->method('moveAttachmentToFinalPlace')->with(self::anything(), 'rename', '/var/tmp')->willReturn(true);

        $submitted_value_info = [
            'description' => '',
            'name'        => 'readme.mkd',
            'size'        => 123,
            'type'        => 'text/plain',
            'tmp_name'    => '/var/tmp',
            'is_moved'    => true,
        ];

        $field = FilesFieldBuilder::aFileField(1234)->build();

        $creator = $this->getMockBuilder(AttachmentForTraditionalUploadCreator::class)
            ->setConstructorArgs([$mover, $rule_file])
            ->onlyMethods(['save'])
            ->getMock();

        $creator->method('save')->willReturn(true);

        $url_mapping = $this->createMock(CreatedFileURLMapping::class);
        $url_mapping->expects($this->never())->method('add');

        $attachment = $creator->createAttachment($current_user, $field, $submitted_value_info, $url_mapping);
        self::assertEquals(101, $attachment->getSubmittedBy());
    }
}
