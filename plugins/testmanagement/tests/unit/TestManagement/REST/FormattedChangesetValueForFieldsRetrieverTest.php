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
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PFUser;
use PHPUnit\Framework\TestCase;
use Tracker;
use Tracker_Artifact_ChangesetValue_File;
use Tracker_FileInfo;
use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\Artifact\FileUploadData;
use Tuleap\Tracker\Artifact\FileUploadDataProvider;

class FormattedChangesetValueForFieldsRetrieverTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var FileUploadDataProvider|Mockery\MockInterface|FileUploadDataProvider
     */
    private $file_upload_data_provider;
    /**
     * @var FormattedChangesetValueForFileFieldRetriever
     */
    private $formatted_changeset_value_for_field_retriever;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|Artifact
     */
    private $artifact;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|PFUser
     */
    private $user;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|Tracker
     */
    private $tracker;

    protected function setUp(): void
    {
        $this->tracker  = Mockery::mock(Tracker::class);
        $this->artifact = Mockery::mock(Artifact::class);
        $this->artifact->shouldReceive('getTracker')->andReturn($this->tracker);

        $this->user                                          = Mockery::mock(PFUser::class);
        $this->file_upload_data_provider                     = Mockery::mock(FileUploadDataProvider::class);
        $this->formatted_changeset_value_for_field_retriever = new FormattedChangesetValueForFileFieldRetriever(
            $this->file_upload_data_provider
        );
    }

    public function testGetFormattedChangesetValueForFieldFile(): void
    {
        $file_1 = Mockery::mock(Tracker_FileInfo::class);
        $file_1->shouldReceive('getId')->andReturn(1);

        $file_2 = Mockery::mock(Tracker_FileInfo::class);
        $file_2->shouldReceive('getId')->andReturn(2);

        $files = [$file_1, $file_2];

        $changeset_value = Mockery::mock(Tracker_Artifact_ChangesetValue_File::class);
        $changeset_value->shouldReceive('getFiles')->andReturn($files);

        $field = Mockery::mock(\Tracker_FormElement_Field_File::class);
        $field->shouldReceive('getId')->andReturn(112);
        $field->shouldReceive('getLastChangesetValue')->andReturn($changeset_value);

        $field_upload_data = Mockery::mock(FileUploadData::class);
        $field_upload_data->shouldReceive('getField')->andReturn($field);

        $this->file_upload_data_provider->shouldReceive('getFileUploadData')->andReturn($field_upload_data);
        $uploaded_file_ids = [14];

        $result = $this->formatted_changeset_value_for_field_retriever
            ->getFormattedChangesetValueForFieldFile($uploaded_file_ids, $this->artifact, $this->user);

        $this->assertEquals([14, 1, 2], $result->value);
        $this->assertEquals(112, $result->field_id);
    }

    public function testGetFormattedChangesetValueForFieldFileReturnsRestExceptionIfNoFileField(): void
    {
        $field_upload_data = null;

        $this->file_upload_data_provider->shouldReceive('getFileUploadData')->andReturn($field_upload_data);
        $uploaded_file_ids = [14];

        $this->expectException(RestException::class);
        $this->expectExceptionCode(400);

        $this->formatted_changeset_value_for_field_retriever
            ->getFormattedChangesetValueForFieldFile($uploaded_file_ids, $this->artifact, $this->user);
    }

    public function testGetFormattedChangesetValueForFieldFileShouldReturnsOnlyNewValueIfTheirIsNoChangesetValue(): void
    {
        $changeset_value = null;

        $field = Mockery::mock(\Tracker_FormElement_Field_File::class);
        $field->shouldReceive('getId')->andReturn(112);
        $field->shouldReceive('getLastChangesetValue')->andReturn($changeset_value);

        $field_upload_data = Mockery::mock(FileUploadData::class);
        $field_upload_data->shouldReceive('getField')->andReturn($field);

        $this->file_upload_data_provider->shouldReceive('getFileUploadData')->andReturn($field_upload_data);

        $uploaded_file_ids = [14];

        $result = $this->formatted_changeset_value_for_field_retriever
            ->getFormattedChangesetValueForFieldFile($uploaded_file_ids, $this->artifact, $this->user);

        $this->assertEquals([14], $result->value);
        $this->assertEquals(112, $result->field_id);
    }

    public function testGetFormattedChangesetValueForFieldFileShouldReturnsOnlyNewValueIfTheirIsNoFileInChangesetValue(): void
    {
        $changeset_value = Mockery::mock(Tracker_Artifact_ChangesetValue_File::class);
        $changeset_value->shouldReceive('getFiles')->andReturn([]);

        $field = Mockery::mock(\Tracker_FormElement_Field_File::class);
        $field->shouldReceive('getId')->andReturn(112);
        $field->shouldReceive('getLastChangesetValue')->andReturn($changeset_value);

        $field_upload_data = Mockery::mock(FileUploadData::class);
        $field_upload_data->shouldReceive('getField')->andReturn($field);

        $this->file_upload_data_provider->shouldReceive('getFileUploadData')->andReturn($field_upload_data);
        $uploaded_file_ids = [14];

        $result = $this->formatted_changeset_value_for_field_retriever
            ->getFormattedChangesetValueForFieldFile($uploaded_file_ids, $this->artifact, $this->user);

        $this->assertEquals([14], $result->value);
        $this->assertEquals(112, $result->field_id);
    }
}
