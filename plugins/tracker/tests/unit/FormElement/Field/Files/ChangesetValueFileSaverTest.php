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

use ForgeConfig;
use PFUser;
use PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles;
use Tracker_Artifact_ChangesetValue_File;
use Tracker_FileInfo;
use Tuleap\ForgeConfigSandbox;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Tracker\Test\Builders\ChangesetTestBuilder;
use Tuleap\Tracker\Test\Builders\Fields\FilesFieldBuilder;

#[DisableReturnValueGenerationForTestDoubles]
final class ChangesetValueFileSaverTest extends TestCase
{
    use ForgeConfigSandbox;

    public function testReturnsTrueWhenThereIsNothingToSaveForANewArtifact(): void
    {
        ForgeConfig::set('sys_max_size_upload', 1024);
        $dao                = $this->createMock(FileFieldValueDao::class);
        $attachment_creator = $this->createMock(AttachmentCreator::class);
        $field              = FilesFieldBuilder::aFileField(354)->build();

        $changeset_value_id = 12345;

        $previous_changeset_value = null;

        $value = [];

        $savior = $this->getMockBuilder(ChangesetValueFileSaver::class)
            ->setConstructorArgs([$dao, $attachment_creator])
            ->onlyMethods(['initFolder'])
            ->getMock();
        $savior->method('initFolder');

        self::assertTrue(
            $savior->saveValue(
                UserTestBuilder::buildWithDefaults(),
                $field,
                $changeset_value_id,
                $value,
                $previous_changeset_value,
                new CreatedFileURLMapping()
            )
        );
    }

    public function testItDuplicatesPreviousValues(): void
    {
        ForgeConfig::set('sys_max_size_upload', 1024);
        $dao                = $this->createMock(FileFieldValueDao::class);
        $attachment_creator = $this->createMock(AttachmentCreator::class);
        $field              = FilesFieldBuilder::aFileField(354)->build();
        $changeset_value_id = 12345;

        $previous_file_1 = new Tracker_FileInfo(69, $field, 101, '', '', 0, 'image/png');
        $previous_file_2 = new Tracker_FileInfo(70, $field, 101, '', '', 0, 'image/png');

        $previous_changeset_value = new Tracker_Artifact_ChangesetValue_File(
            1,
            ChangesetTestBuilder::aChangeset(63412)->build(),
            $field,
            0,
            [$previous_file_1, $previous_file_2]
        );

        $value = [];

        $savior = $this->getMockBuilder(ChangesetValueFileSaver::class)
            ->setConstructorArgs([$dao, $attachment_creator])
            ->onlyMethods(['initFolder'])
            ->getMock();

        $savior->method('initFolder');

        $dao->method('create')->with($changeset_value_id, [69, 70])->willReturn(true);

        self::assertTrue(
            $savior->saveValue(
                UserTestBuilder::buildWithDefaults(),
                $field,
                $changeset_value_id,
                $value,
                $previous_changeset_value,
                new CreatedFileURLMapping()
            )
        );
    }

    public function testItReturnsFalseIfItCannotDuplicatePreviousValues(): void
    {
        ForgeConfig::set('sys_max_size_upload', 1024);
        $dao                = $this->createMock(FileFieldValueDao::class);
        $attachment_creator = $this->createMock(AttachmentCreator::class);
        $field              = FilesFieldBuilder::aFileField(354)->build();
        $changeset_value_id = 12345;

        $previous_file_1 = new Tracker_FileInfo(69, $field, 101, '', '', 0, 'image/png');
        $previous_file_2 = new Tracker_FileInfo(70, $field, 101, '', '', 0, 'image/png');

        $previous_changeset_value = new Tracker_Artifact_ChangesetValue_File(
            1,
            ChangesetTestBuilder::aChangeset(6145)->build(),
            $field,
            0,
            [$previous_file_1, $previous_file_2]
        );

        $value = [];

        $savior = $this->getMockBuilder(ChangesetValueFileSaver::class)
            ->setConstructorArgs([$dao, $attachment_creator])
            ->onlyMethods(['initFolder'])
            ->getMock();

        $savior->method('initFolder');

        $dao->method('create')->with($changeset_value_id, [69, 70])->willReturn(false);

        self::assertFalse(
            $savior->saveValue(
                UserTestBuilder::buildWithDefaults(),
                $field,
                $changeset_value_id,
                $value,
                $previous_changeset_value,
                new CreatedFileURLMapping()
            )
        );
    }

    public function testItDeletePreviousValues(): void
    {
        ForgeConfig::set('sys_max_size_upload', 1024);
        $dao                = $this->createMock(FileFieldValueDao::class);
        $attachment_creator = $this->createMock(AttachmentCreator::class);
        $field              = FilesFieldBuilder::aFileField(354)->build();
        $changeset_value_id = 12345;

        $previous_file_1 = new Tracker_FileInfo(69, $field, 101, '', '', 0, 'image/png');
        $previous_file_2 = new Tracker_FileInfo(70, $field, 101, '', '', 0, 'image/png');

        $previous_changeset_value = new Tracker_Artifact_ChangesetValue_File(
            1,
            ChangesetTestBuilder::aChangeset(6145)->build(),
            $field,
            0,
            [$previous_file_1, $previous_file_2]
        );

        $value = [
            'delete' => [70],
        ];

        $savior = $this->getMockBuilder(ChangesetValueFileSaver::class)
            ->setConstructorArgs([$dao, $attachment_creator])
            ->onlyMethods(['initFolder'])
            ->getMock();

        $savior->method('initFolder');

        $dao->method('create')->with($changeset_value_id, [69])->willReturn(true);

        self::assertTrue(
            $savior->saveValue(
                UserTestBuilder::buildWithDefaults(),
                $field,
                $changeset_value_id,
                $value,
                $previous_changeset_value,
                new CreatedFileURLMapping()
            )
        );
    }

    public function testSavesNewFiles(): void
    {
        ForgeConfig::set('sys_max_size_upload', 1024);
        $dao                = $this->createMock(FileFieldValueDao::class);
        $attachment_creator = $this->createMock(AttachmentCreator::class);
        $current_user       = UserTestBuilder::buildWithDefaults();
        $field              = FilesFieldBuilder::aFileField(354)->build();
        $url_mapping        = new CreatedFileURLMapping();

        $changeset_value_id = 12345;

        $previous_changeset_value = null;

        $submitted_file_1 = [
            'description' => '',
            'name'        => 'readme.mkd',
            'size'        => 123,
            'type'        => 'text/plain',
            'tmp_name'    => '/var/tmp/1',
        ];
        $submitted_file_2 = [
            'description' => '',
            'name'        => 'BradPitt.jpg',
            'size'        => 456,
            'type'        => 'image/jpg',
            'tmp_name'    => '/var/tmp/2',
        ];

        $value = [$submitted_file_1, $submitted_file_2];

        $attachment_1 = new Tracker_FileInfo(1, $field, 101, '', '', 0, 'image/png');
        $attachment_2 = new Tracker_FileInfo(2, $field, 101, '', '', 0, 'image/png');

        $savior = $this->getMockBuilder(ChangesetValueFileSaver::class)
            ->setConstructorArgs([$dao, $attachment_creator])
            ->onlyMethods(['initFolder'])
            ->getMock();

        $savior->method('initFolder');
        $attachment_creator->method('createAttachment')->with($current_user, $field, self::anything(), $url_mapping)
            ->willReturnCallback(static fn(PFUser $current_user, FilesField $field, array $submitted_value_info) => match ($submitted_value_info) {
                $submitted_file_1 => $attachment_1,
                $submitted_file_2 => $attachment_2,
            });
        $dao->method('create')->with($changeset_value_id, [1, 2])->willReturn(true);

        self::assertTrue(
            $savior->saveValue(
                $current_user,
                $field,
                $changeset_value_id,
                $value,
                $previous_changeset_value,
                $url_mapping
            )
        );
    }

    public function testReturnsFalseIfItCannotSaveNewFiles(): void
    {
        ForgeConfig::set('sys_max_size_upload', 1024);
        $dao                = $this->createMock(FileFieldValueDao::class);
        $attachment_creator = $this->createMock(AttachmentCreator::class);
        $current_user       = UserTestBuilder::buildWithDefaults();
        $field              = FilesFieldBuilder::aFileField(354)->build();
        $url_mapping        = new CreatedFileURLMapping();

        $changeset_value_id = 12345;

        $previous_changeset_value = null;

        $submitted_file_1 = [
            'description' => '',
            'name'        => 'readme.mkd',
            'size'        => 123,
            'type'        => 'text/plain',
            'tmp_name'    => '/var/tmp/1',
        ];
        $submitted_file_2 = [
            'description' => '',
            'name'        => 'BradPitt.jpg',
            'size'        => 456,
            'type'        => 'image/jpg',
            'tmp_name'    => '/var/tmp/2',
        ];

        $value = [$submitted_file_1, $submitted_file_2];

        $attachment_1 = new Tracker_FileInfo(1, $field, 101, '', '', 0, 'image/png');
        $attachment_2 = new Tracker_FileInfo(2, $field, 101, '', '', 0, 'image/png');

        $savior = $this->getMockBuilder(ChangesetValueFileSaver::class)
            ->setConstructorArgs([$dao, $attachment_creator])
            ->onlyMethods(['initFolder'])
            ->getMock();

        $savior->method('initFolder');
        $attachment_creator->method('createAttachment')->with($current_user, $field, self::anything(), $url_mapping)
            ->willReturnCallback(static fn(PFUser $current_user, FilesField $field, array $submitted_value_info) => match ($submitted_value_info) {
                $submitted_file_1 => $attachment_1,
                $submitted_file_2 => $attachment_2,
            });
        $dao->method('create')->with($changeset_value_id, [1, 2])->willReturn(false);

        self::assertFalse(
            $savior->saveValue(
                $current_user,
                $field,
                $changeset_value_id,
                $value,
                $previous_changeset_value,
                $url_mapping
            )
        );
    }

    public function testIgnoresFilesThatHaveNotBeenCreated(): void
    {
        ForgeConfig::set('sys_max_size_upload', 1024);
        $dao                = $this->createMock(FileFieldValueDao::class);
        $attachment_creator = $this->createMock(AttachmentCreator::class);
        $current_user       = UserTestBuilder::buildWithDefaults();
        $field              = FilesFieldBuilder::aFileField(354)->build();
        $url_mapping        = new CreatedFileURLMapping();

        $changeset_value_id = 12345;

        $previous_changeset_value = null;

        $submitted_file_1 = [
            'description' => '',
            'name'        => 'readme.mkd',
            'size'        => 123,
            'type'        => 'text/plain',
            'tmp_name'    => '/var/tmp/1',
        ];
        $submitted_file_2 = [
            'description' => '',
            'name'        => 'BradPitt.jpg',
            'size'        => 456,
            'type'        => 'image/jpg',
            'tmp_name'    => '/var/tmp/2',
        ];

        $value = [$submitted_file_1, $submitted_file_2];

        $attachment_2 = new Tracker_FileInfo(2, $field, 101, '', '', 0, 'image/png');

        $savior = $this->getMockBuilder(ChangesetValueFileSaver::class)
            ->setConstructorArgs([$dao, $attachment_creator])
            ->onlyMethods(['initFolder'])
            ->getMock();

        $savior->method('initFolder');
        $attachment_creator->method('createAttachment')->with($current_user, $field, self::anything(), $url_mapping)
            ->willReturnCallback(static fn(PFUser $current_user, FilesField $field, array $submitted_value_info) => match ($submitted_value_info) {
                $submitted_file_1 => null,
                $submitted_file_2 => $attachment_2,
            });
        $dao->method('create')->with($changeset_value_id, [2])->willReturn(true);

        self::assertTrue(
            $savior->saveValue(
                $current_user,
                $field,
                $changeset_value_id,
                $value,
                $previous_changeset_value,
                $url_mapping
            )
        );
    }
}
