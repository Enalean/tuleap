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

use ForgeConfig;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PFUser;
use PHPUnit\Framework\TestCase;
use Tracker_Artifact_ChangesetValue_File;
use Tracker_FormElement_Field_File;
use Tracker_FormElement_Field_Value_FileDao;
use Tuleap\ForgeConfigSandbox;

class ChangesetValueFileSaverTest extends TestCase
{
    use MockeryPHPUnitIntegration;
    use ForgeConfigSandbox;

    public function testReturnsTrueWhenThereIsNothingToSaveForANewArtifact(): void
    {
        ForgeConfig::set('sys_max_size_upload', 1024);
        $dao                = \Mockery::mock(Tracker_FormElement_Field_Value_FileDao::class);
        $attachment_creator = \Mockery::mock(AttachmentCreator::class);
        $current_user       = \Mockery::mock(PFUser::class);
        $field              = \Mockery::mock(Tracker_FormElement_Field_File::class);
        $url_mapping         = \Mockery::mock(CreatedFileURLMapping::class);

        $changeset_value_id = 12345;

        $previous_changeset_value = null;

        $value = [];

        $savior = \Mockery::mock(ChangesetValueFileSaver::class . '[initFolder]', [$dao, $attachment_creator]);
        \assert($savior instanceof ChangesetValueFileSaver || $savior instanceof \Mockery\MockInterface);
        $savior->shouldAllowMockingProtectedMethods();

        $savior->shouldReceive('initFolder');

        $this->assertTrue(
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

    public function testItDuplicatesPreviousValues(): void
    {
        ForgeConfig::set('sys_max_size_upload', 1024);
        $dao                = \Mockery::mock(Tracker_FormElement_Field_Value_FileDao::class);
        $attachment_creator = \Mockery::mock(AttachmentCreator::class);
        $current_user       = \Mockery::mock(PFUser::class);
        $field              = \Mockery::mock(Tracker_FormElement_Field_File::class);
        $url_mapping         = \Mockery::mock(CreatedFileURLMapping::class);

        $changeset_value_id = 12345;

        $previous_file_1 = \Mockery::mock(\Tracker_FileInfo::class);
        $previous_file_1->shouldReceive('getId')->andReturn(69);

        $previous_file_2 = \Mockery::mock(\Tracker_FileInfo::class);
        $previous_file_2->shouldReceive('getId')->andReturn(70);

        $previous_changeset_value = new Tracker_Artifact_ChangesetValue_File(
            1,
            \Mockery::mock(\Tracker_Artifact_Changeset::class),
            $field,
            0,
            [$previous_file_1, $previous_file_2]
        );

        $value = [];

        $savior = \Mockery::mock(ChangesetValueFileSaver::class . '[initFolder]', [$dao, $attachment_creator]);
        \assert($savior instanceof ChangesetValueFileSaver || $savior instanceof \Mockery\MockInterface);
        $savior->shouldAllowMockingProtectedMethods();

        $savior->shouldReceive('initFolder');

        $dao->shouldReceive('create')->with($changeset_value_id, [69, 70])->andReturn(true);

        $this->assertTrue(
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

    public function testItReturnsFalseIfItCannotDuplicatePreviousValues(): void
    {
        ForgeConfig::set('sys_max_size_upload', 1024);
        $dao                = \Mockery::mock(Tracker_FormElement_Field_Value_FileDao::class);
        $attachment_creator = \Mockery::mock(AttachmentCreator::class);
        $current_user       = \Mockery::mock(PFUser::class);
        $field              = \Mockery::mock(Tracker_FormElement_Field_File::class);
        $url_mapping         = \Mockery::mock(CreatedFileURLMapping::class);

        $changeset_value_id = 12345;

        $previous_file_1 = \Mockery::mock(\Tracker_FileInfo::class);
        $previous_file_1->shouldReceive('getId')->andReturn(69);

        $previous_file_2 = \Mockery::mock(\Tracker_FileInfo::class);
        $previous_file_2->shouldReceive('getId')->andReturn(70);

        $previous_changeset_value = new Tracker_Artifact_ChangesetValue_File(
            1,
            \Mockery::mock(\Tracker_Artifact_Changeset::class),
            $field,
            0,
            [$previous_file_1, $previous_file_2]
        );

        $value = [];

        $savior = \Mockery::mock(ChangesetValueFileSaver::class . '[initFolder]', [$dao, $attachment_creator]);
        \assert($savior instanceof ChangesetValueFileSaver || $savior instanceof \Mockery\MockInterface);
        $savior->shouldAllowMockingProtectedMethods();

        $savior->shouldReceive('initFolder');

        $dao->shouldReceive('create')->with($changeset_value_id, [69, 70])->andReturn(false);

        $this->assertFalse(
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

    public function testItDeletePreviousValues(): void
    {
        ForgeConfig::set('sys_max_size_upload', 1024);
        $dao                = \Mockery::mock(Tracker_FormElement_Field_Value_FileDao::class);
        $attachment_creator = \Mockery::mock(AttachmentCreator::class);
        $current_user       = \Mockery::mock(PFUser::class);
        $field              = \Mockery::mock(Tracker_FormElement_Field_File::class);
        $url_mapping         = \Mockery::mock(CreatedFileURLMapping::class);

        $changeset_value_id = 12345;

        $previous_file_1 = \Mockery::mock(\Tracker_FileInfo::class);
        $previous_file_1->shouldReceive('getId')->andReturn(69);

        $previous_file_2 = \Mockery::mock(\Tracker_FileInfo::class);
        $previous_file_2->shouldReceive('getId')->andReturn(70);

        $previous_changeset_value = new Tracker_Artifact_ChangesetValue_File(
            1,
            \Mockery::mock(\Tracker_Artifact_Changeset::class),
            $field,
            0,
            [$previous_file_1, $previous_file_2]
        );

        $value = [
            'delete' => [70]
        ];

        $savior = \Mockery::mock(ChangesetValueFileSaver::class . '[initFolder]', [$dao, $attachment_creator]);
        \assert($savior instanceof ChangesetValueFileSaver || $savior instanceof \Mockery\MockInterface);
        $savior->shouldAllowMockingProtectedMethods();

        $savior->shouldReceive('initFolder');

        $dao->shouldReceive('create')->with($changeset_value_id, [69])->andReturn(true);
        $previous_file_2->shouldReceive('deleteFiles');

        $this->assertTrue(
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

    public function testSavesNewFiles(): void
    {
        ForgeConfig::set('sys_max_size_upload', 1024);
        $dao                = \Mockery::mock(Tracker_FormElement_Field_Value_FileDao::class);
        $attachment_creator = \Mockery::mock(AttachmentCreator::class);
        $current_user       = \Mockery::mock(PFUser::class);
        $field              = \Mockery::mock(Tracker_FormElement_Field_File::class);
        $url_mapping         = \Mockery::mock(CreatedFileURLMapping::class);

        $changeset_value_id = 12345;

        $previous_changeset_value = null;

        $submitted_file_1 = [
            'description' => '',
            'name'        => 'readme.mkd',
            'size'        => 123,
            'type'        => 'text/plain',
            'tmp_name'    => '/var/tmp/1'
        ];
        $submitted_file_2 = [
            'description' => '',
            'name'        => 'BradPitt.jpg',
            'size'        => 456,
            'type'        => 'image/jpg',
            'tmp_name'    => '/var/tmp/2'
        ];

        $value = [$submitted_file_1, $submitted_file_2];

        $attachment_1 = \Mockery::mock(\Tracker_FileInfo::class);
        $attachment_1->shouldReceive('getId')->andReturn(1);
        $attachment_2 = \Mockery::mock(\Tracker_FileInfo::class);
        $attachment_2->shouldReceive('getId')->andReturn(2);

        $savior = \Mockery::mock(ChangesetValueFileSaver::class . '[initFolder]', [$dao, $attachment_creator]);
        \assert($savior instanceof ChangesetValueFileSaver || $savior instanceof \Mockery\MockInterface);
        $savior->shouldAllowMockingProtectedMethods();

        $savior->shouldReceive('initFolder');
        $attachment_creator
            ->shouldReceive('createAttachment')
            ->with($current_user, $field, $submitted_file_1, $url_mapping)
            ->andReturn($attachment_1);
        $attachment_creator
            ->shouldReceive('createAttachment')
            ->with($current_user, $field, $submitted_file_2, $url_mapping)
            ->andReturn($attachment_2);
        $dao->shouldReceive('create')->with($changeset_value_id, [1, 2])->andReturn(true);

        $this->assertTrue(
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
        $dao                = \Mockery::mock(Tracker_FormElement_Field_Value_FileDao::class);
        $attachment_creator = \Mockery::mock(AttachmentCreator::class);
        $current_user       = \Mockery::mock(PFUser::class);
        $field              = \Mockery::mock(Tracker_FormElement_Field_File::class);
        $url_mapping         = \Mockery::mock(CreatedFileURLMapping::class);

        $changeset_value_id = 12345;

        $previous_changeset_value = null;

        $submitted_file_1 = [
            'description' => '',
            'name'        => 'readme.mkd',
            'size'        => 123,
            'type'        => 'text/plain',
            'tmp_name'    => '/var/tmp/1'
        ];
        $submitted_file_2 = [
            'description' => '',
            'name'        => 'BradPitt.jpg',
            'size'        => 456,
            'type'        => 'image/jpg',
            'tmp_name'    => '/var/tmp/2'
        ];

        $value = [$submitted_file_1, $submitted_file_2];

        $attachment_1 = \Mockery::mock(\Tracker_FileInfo::class);
        $attachment_1->shouldReceive('getId')->andReturn(1);
        $attachment_2 = \Mockery::mock(\Tracker_FileInfo::class);
        $attachment_2->shouldReceive('getId')->andReturn(2);

        $savior = \Mockery::mock(ChangesetValueFileSaver::class . '[initFolder]', [$dao, $attachment_creator]);
        \assert($savior instanceof ChangesetValueFileSaver || $savior instanceof \Mockery\MockInterface);
        $savior->shouldAllowMockingProtectedMethods();

        $savior->shouldReceive('initFolder');
        $attachment_creator
            ->shouldReceive('createAttachment')
            ->with($current_user, $field, $submitted_file_1, $url_mapping)
            ->andReturn($attachment_1);
        $attachment_creator
            ->shouldReceive('createAttachment')
            ->with($current_user, $field, $submitted_file_2, $url_mapping)
            ->andReturn($attachment_2);
        $dao->shouldReceive('create')->with($changeset_value_id, [1, 2])->andReturn(false);

        $this->assertFalse(
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
        $dao                = \Mockery::mock(Tracker_FormElement_Field_Value_FileDao::class);
        $attachment_creator = \Mockery::mock(AttachmentCreator::class);
        $current_user       = \Mockery::mock(PFUser::class);
        $field              = \Mockery::mock(Tracker_FormElement_Field_File::class);
        $url_mapping         = \Mockery::mock(CreatedFileURLMapping::class);

        $changeset_value_id = 12345;

        $previous_changeset_value = null;

        $submitted_file_1 = [
            'description' => '',
            'name'        => 'readme.mkd',
            'size'        => 123,
            'type'        => 'text/plain',
            'tmp_name'    => '/var/tmp/1'
        ];
        $submitted_file_2 = [
            'description' => '',
            'name'        => 'BradPitt.jpg',
            'size'        => 456,
            'type'        => 'image/jpg',
            'tmp_name'    => '/var/tmp/2'
        ];

        $value = [$submitted_file_1, $submitted_file_2];

        $attachment_2 = \Mockery::mock(\Tracker_FileInfo::class);
        $attachment_2->shouldReceive('getId')->andReturn(2);

        $savior = \Mockery::mock(ChangesetValueFileSaver::class . '[initFolder]', [$dao, $attachment_creator]);
        \assert($savior instanceof ChangesetValueFileSaver || $savior instanceof \Mockery\MockInterface);
        $savior->shouldAllowMockingProtectedMethods();

        $savior->shouldReceive('initFolder');
        $attachment_creator
            ->shouldReceive('createAttachment')
            ->with($current_user, $field, $submitted_file_1, $url_mapping)
            ->andReturn(null);
        $attachment_creator
            ->shouldReceive('createAttachment')
            ->with($current_user, $field, $submitted_file_2, $url_mapping)
            ->andReturn($attachment_2);
        $dao->shouldReceive('create')->with($changeset_value_id, [2])->andReturn(true);

        $this->assertTrue(
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
