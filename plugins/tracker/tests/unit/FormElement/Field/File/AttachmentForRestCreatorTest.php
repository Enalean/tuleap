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

use PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles;
use Rule_File;
use Tracker_Artifact_Attachment_TemporaryFile;
use Tracker_Artifact_Attachment_TemporaryFileManager;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Tracker\Test\Builders\Fields\FileFieldBuilder;

#[DisableReturnValueGenerationForTestDoubles]
final class AttachmentForRestCreatorTest extends TestCase
{
    public function testCreateAttachment(): void
    {
        $rule_file = $this->createMock(Rule_File::class);
        $rule_file->method('isValid')->willReturn(true);

        $current_user = UserTestBuilder::buildWithId(101);

        $mover = $this->createMock(AttachmentToFinalPlaceMover::class);
        $mover->method('moveAttachmentToFinalPlace')->with(self::anything(), 'rename', '/var/tmp')->willReturn(true);

        $submitted_value_info = ['id' => 42];

        $field = FileFieldBuilder::aFileField(653)->build();

        $temporary_file = new Tracker_Artifact_Attachment_TemporaryFile(
            42,
            'readme.mkd',
            '',
            '',
            0,
            101,
            123,
            'text/plain',
        );

        $temporary_file_manager = $this->createMock(Tracker_Artifact_Attachment_TemporaryFileManager::class);
        $temporary_file_manager->method('getFileByTemporaryName')->with(42)->willReturn($temporary_file);
        $temporary_file_manager->method('exists')->with($current_user, 42)->willReturn(true);
        $temporary_file_manager->method('getPath')->with($current_user, 42)->willReturn('/var/tmp');
        $temporary_file_manager->method('removeTemporaryFileInDBByTemporaryName')->with(42);

        $next_creator_in_chain = $this->createMock(AttachmentCreator::class);

        $creator = $this->getMockBuilder(AttachmentForRestCreator::class)
            ->onlyMethods(['delete'])
            ->setConstructorArgs([
                $mover,
                $temporary_file_manager,
                $next_creator_in_chain,
                $rule_file,
            ])
            ->getMock();

        $creator->expects($this->never())->method('delete');

        $url_mapping = $this->createMock(CreatedFileURLMapping::class);
        $url_mapping->expects($this->never())->method('add');

        $attachment = $creator->createAttachment($current_user, $field, $submitted_value_info, $url_mapping);
        self::assertEquals('readme.mkd', $attachment->getFilename());
    }

    public function testItReturnsNullIfMoveToFinalPlaceIsNotPossible(): void
    {
        $rule_file = $this->createMock(Rule_File::class);
        $rule_file->method('isValid')->willReturn(true);

        $current_user = UserTestBuilder::buildWithId(101);

        $mover = $this->createMock(AttachmentToFinalPlaceMover::class);
        $mover->method('moveAttachmentToFinalPlace')->with(self::anything(), 'rename', '/var/tmp')->willReturn(false);

        $submitted_value_info = ['id' => 42];

        $field = FileFieldBuilder::aFileField(654)->build();

        $temporary_file = new Tracker_Artifact_Attachment_TemporaryFile(
            42,
            'readme.mkd',
            '',
            '',
            0,
            101,
            123,
            'text/plain',
        );

        $temporary_file_manager = $this->createMock(Tracker_Artifact_Attachment_TemporaryFileManager::class);
        $temporary_file_manager->method('getFileByTemporaryName')->with(42)->willReturn($temporary_file);
        $temporary_file_manager->method('exists')->with($current_user, 42)->willReturn(true);
        $temporary_file_manager->method('getPath')->with($current_user, 42)->willReturn('/var/tmp');
        $temporary_file_manager->method('removeTemporaryFileInDBByTemporaryName')->with(42);

        $creator = $this->getMockBuilder(AttachmentForRestCreator::class)
            ->onlyMethods(['delete'])
            ->setConstructorArgs([
                $mover,
                $temporary_file_manager,
                $this->createStub(AttachmentCreator::class),
                $rule_file,
            ])
            ->getMock();

        $creator->expects($this->never())->method('delete');

        $url_mapping = $this->createMock(CreatedFileURLMapping::class);
        $url_mapping->expects($this->never())->method('add');

        $attachment = $creator->createAttachment($current_user, $field, $submitted_value_info, $url_mapping);
        self::assertNull($attachment);
    }

    public function testItReturnsNullIfTemporaryFileDoesNotExist(): void
    {
        $rule_file = $this->createMock(Rule_File::class);
        $rule_file->method('isValid')->willReturn(true);

        $current_user = UserTestBuilder::buildWithId(101);

        $mover = $this->createMock(AttachmentToFinalPlaceMover::class);

        $submitted_value_info = ['id' => 42];

        $field = FileFieldBuilder::aFileField(654)->build();

        $temporary_file = new Tracker_Artifact_Attachment_TemporaryFile(
            42,
            'readme.mkd',
            '',
            '',
            0,
            101,
            123,
            'text/plain',
        );

        $temporary_file_manager = $this->createMock(Tracker_Artifact_Attachment_TemporaryFileManager::class);
        $temporary_file_manager->method('getFileByTemporaryName')->with(42)->willReturn($temporary_file);
        $temporary_file_manager->method('exists')->with($current_user, 42)->willReturn(false);
        $temporary_file_manager->method('getPath')->with($current_user, 42)->willReturn('/var/tmp');
        $temporary_file_manager->method('removeTemporaryFileInDBByTemporaryName')->with(42);

        $creator = $this->getMockBuilder(AttachmentForRestCreator::class)
            ->onlyMethods(['delete'])
            ->setConstructorArgs([
                $mover,
                $temporary_file_manager,
                $this->createStub(AttachmentCreator::class),
                $rule_file,
            ])
            ->getMock();

        $creator->expects($this->once())->method('delete');

        $url_mapping = $this->createMock(CreatedFileURLMapping::class);
        $url_mapping->expects($this->never())->method('add');

        $attachment = $creator->createAttachment($current_user, $field, $submitted_value_info, $url_mapping);
        self::assertNull($attachment);
    }

    public function testItDelegatesToNextCreatorInChainIfThereIsNoTemporaryFileForGivenId(): void
    {
        $rule_file = $this->createMock(Rule_File::class);
        $rule_file->method('isValid')->willReturn(true);

        $current_user = UserTestBuilder::buildWithId(101);

        $mover = $this->createMock(AttachmentToFinalPlaceMover::class);

        $submitted_value_info = ['id' => 42];

        $field = FileFieldBuilder::aFileField(654)->build();

        $url_mapping = $this->createMock(CreatedFileURLMapping::class);

        $temporary_file_manager = $this->createMock(Tracker_Artifact_Attachment_TemporaryFileManager::class);
        $temporary_file_manager->method('getFileByTemporaryName')->with(42)->willReturn(null);

        $next_creator_in_chain = $this->createMock(AttachmentCreator::class);
        $next_creator_in_chain->method('createAttachment')->with($current_user, $field, $submitted_value_info, $url_mapping);

        $creator = $this->getMockBuilder(AttachmentForRestCreator::class)
            ->onlyMethods(['delete'])
            ->setConstructorArgs([
                $mover,
                $temporary_file_manager,
                $next_creator_in_chain,
                $rule_file,
            ])
            ->getMock();

        $creator->expects($this->never())->method('delete');

        $url_mapping->expects($this->never())->method('add');

        $attachment = $creator->createAttachment($current_user, $field, $submitted_value_info, $url_mapping);
        self::assertNull($attachment);
    }

    public function testItDelegatesToNextCreatorInChainIfThereIsNoIdEntryInSubmittedValueInfo(): void
    {
        $rule_file = $this->createMock(Rule_File::class);
        $rule_file->method('isValid')->willReturn(true);

        $current_user = UserTestBuilder::buildWithId(101);

        $mover = $this->createMock(AttachmentToFinalPlaceMover::class);

        $submitted_value_info = [];

        $field = FileFieldBuilder::aFileField(654)->build();

        $url_mapping = $this->createMock(CreatedFileURLMapping::class);

        $next_creator_in_chain = $this->createMock(AttachmentCreator::class);
        $next_creator_in_chain->method('createAttachment')->with($current_user, $field, $submitted_value_info, $url_mapping);

        $creator = $this->getMockBuilder(AttachmentForRestCreator::class)
            ->onlyMethods(['delete'])
            ->setConstructorArgs([
                $mover,
                $this->createStub(Tracker_Artifact_Attachment_TemporaryFileManager::class),
                $next_creator_in_chain,
                $rule_file,
            ])
            ->getMock();

        $creator->expects($this->never())->method('delete');

        $url_mapping->expects($this->never())->method('add');

        $attachment = $creator->createAttachment($current_user, $field, $submitted_value_info, $url_mapping);
        self::assertNull($attachment);
    }

    public function testItDelegatesToNextCreatorInChainIfTheFileIsNotValid(): void
    {
        $rule_file = $this->createMock(Rule_File::class);
        $rule_file->method('isValid')->willReturn(false);

        $current_user = UserTestBuilder::buildWithId(101);

        $mover = $this->createMock(AttachmentToFinalPlaceMover::class);

        $submitted_value_info = ['id' => 42];

        $field = FileFieldBuilder::aFileField(654)->build();

        $url_mapping = $this->createMock(CreatedFileURLMapping::class);

        $next_creator_in_chain = $this->createMock(AttachmentCreator::class);
        $next_creator_in_chain->method('createAttachment')->with($current_user, $field, $submitted_value_info, $url_mapping);

        $creator = $this->getMockBuilder(AttachmentForRestCreator::class)
            ->onlyMethods(['delete'])
            ->setConstructorArgs([
                $mover,
                $this->createStub(Tracker_Artifact_Attachment_TemporaryFileManager::class),
                $next_creator_in_chain,
                $rule_file,
            ])
            ->getMock();

        $creator->expects($this->never())->method('delete');

        $url_mapping->expects($this->never())->method('add');

        $attachment = $creator->createAttachment($current_user, $field, $submitted_value_info, $url_mapping);
        self::assertNull($attachment);
    }
}
