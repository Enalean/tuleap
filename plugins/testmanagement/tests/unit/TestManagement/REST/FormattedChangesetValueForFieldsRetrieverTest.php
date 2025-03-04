<?php
/**
 * Copyright (c) Enalean, 2020 - Present. All Rights Reserved.
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

namespace Tuleap\TestManagement\REST;

use Luracast\Restler\RestException;
use PFUser;
use PHPUnit\Framework\MockObject\MockObject;
use Tracker_Artifact_ChangesetValue_File;
use Tracker_FileInfo;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\Artifact\FileUploadData;
use Tuleap\Tracker\Artifact\FileUploadDataProvider;
use Tuleap\Tracker\Test\Builders\ArtifactTestBuilder;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class FormattedChangesetValueForFieldsRetrieverTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private FileUploadDataProvider&MockObject $file_upload_data_provider;
    private FormattedChangesetValueForFileFieldRetriever $formatted_changeset_value_for_field_retriever;
    private Artifact $artifact;
    private PFUser $user;

    protected function setUp(): void
    {
        $this->artifact = ArtifactTestBuilder::anArtifact(1)->build();
        $this->user     = UserTestBuilder::buildWithDefaults();

        $this->file_upload_data_provider                     = $this->createMock(FileUploadDataProvider::class);
        $this->formatted_changeset_value_for_field_retriever = new FormattedChangesetValueForFileFieldRetriever(
            $this->file_upload_data_provider
        );
    }

    public function testGetFormattedChangesetValueForFieldFile(): void
    {
        $file_1 = $this->createMock(Tracker_FileInfo::class);
        $file_1->method('getId')->willReturn(1);

        $file_2 = $this->createMock(Tracker_FileInfo::class);
        $file_2->method('getId')->willReturn(2);

        $files = [$file_1, $file_2];

        $changeset_value = $this->createMock(Tracker_Artifact_ChangesetValue_File::class);
        $changeset_value->method('getFiles')->willReturn($files);

        $field = $this->createMock(\Tracker_FormElement_Field_File::class);
        $field->method('getId')->willReturn(112);
        $field->method('getLastChangesetValue')->willReturn($changeset_value);

        $field_upload_data = $this->createMock(FileUploadData::class);
        $field_upload_data->method('getField')->willReturn($field);

        $this->file_upload_data_provider->method('getFileUploadData')->willReturn($field_upload_data);
        $uploaded_file_ids = [14];
        $deleted_file_ids  = [1];

        $result = $this->formatted_changeset_value_for_field_retriever
            ->getFormattedChangesetValueForFieldFile($uploaded_file_ids, $deleted_file_ids, $this->artifact, $this->user);

        $this->assertEquals([14, 2], $result->value);
        $this->assertEquals(112, $result->field_id);
    }

    public function testGetFormattedChangesetValueForFieldFileReturnsRestExceptionIfNoFileField(): void
    {
        $field_upload_data = null;

        $this->file_upload_data_provider->method('getFileUploadData')->willReturn($field_upload_data);

        $this->expectException(RestException::class);
        $this->expectExceptionCode(400);

        $this->formatted_changeset_value_for_field_retriever
            ->getFormattedChangesetValueForFieldFile([], [], $this->artifact, $this->user);
    }

    public function testGetFormattedChangesetValueForFieldFileShouldReturnsOnlyNewValueIfTheirIsNoChangesetValue(): void
    {
        $changeset_value = null;

        $field = $this->createMock(\Tracker_FormElement_Field_File::class);
        $field->method('getId')->willReturn(112);
        $field->method('getLastChangesetValue')->willReturn($changeset_value);

        $field_upload_data = $this->createMock(FileUploadData::class);
        $field_upload_data->method('getField')->willReturn($field);

        $this->file_upload_data_provider->method('getFileUploadData')->willReturn($field_upload_data);

        $uploaded_file_ids = [14];
        $deleted_file_ids  = [666];

        $result = $this->formatted_changeset_value_for_field_retriever
            ->getFormattedChangesetValueForFieldFile($uploaded_file_ids, $deleted_file_ids, $this->artifact, $this->user);

        $this->assertEquals([14], $result->value);
        $this->assertEquals(112, $result->field_id);
    }

    public function testGetFormattedChangesetValueForFieldFileShouldReturnsOnlyNewValueIfTheirIsNoFileInChangesetValue(): void
    {
        $changeset_value = $this->createMock(Tracker_Artifact_ChangesetValue_File::class);
        $changeset_value->method('getFiles')->willReturn([]);

        $field = $this->createMock(\Tracker_FormElement_Field_File::class);
        $field->method('getId')->willReturn(112);
        $field->method('getLastChangesetValue')->willReturn($changeset_value);

        $field_upload_data = $this->createMock(FileUploadData::class);
        $field_upload_data->method('getField')->willReturn($field);

        $this->file_upload_data_provider->method('getFileUploadData')->willReturn($field_upload_data);
        $uploaded_file_ids = [14];
        $deleted_file_ids  = [666];

        $result = $this->formatted_changeset_value_for_field_retriever
            ->getFormattedChangesetValueForFieldFile($uploaded_file_ids, $deleted_file_ids, $this->artifact, $this->user);

        $this->assertEquals([14], $result->value);
        $this->assertEquals(112, $result->field_id);
    }
}
