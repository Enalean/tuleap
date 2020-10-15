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

namespace Tuleap\Tracker\Artifact;

use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PFUser;
use PHPUnit\Framework\TestCase;
use Tracker;
use Tracker_FileInfo;
use Tracker_FormElementFactory;
use Tuleap\Tracker\Workflow\PostAction\FrozenFields\FrozenFieldDetector;

class FileUploadDataProviderTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|Tracker_FormElementFactory
     */
    private $form_element_factory;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|FrozenFieldDetector
     */
    private $frozen_field_detector;
    /**
     * @var FileUploadDataProvider
     */
    private $file_upload_data_provider;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|Tracker
     */
    private $tracker;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|Artifact
     */
    private $artifact;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|PFUser
     */
    private $user;

    protected function setUp(): void
    {
        $this->form_element_factory  = Mockery::mock(Tracker_FormElementFactory::class);
        $this->frozen_field_detector = Mockery::mock(FrozenFieldDetector::class);
        $this->tracker               = Mockery::mock(Tracker::class);
        $this->artifact              = Mockery::mock(Artifact::class);
        $this->user                  = Mockery::mock(PFUser::class);

        $this->file_upload_data_provider = new FileUploadDataProvider(
            $this->frozen_field_detector,
            $this->form_element_factory
        );
    }

    public function testGetFileUploadData(): void
    {
        $file_1 = Mockery::mock(Tracker_FileInfo::class);
        $file_1->shouldReceive('getId')->andReturn(12);

        $field_1 = Mockery::mock(\Tracker_FormElement_Field_File::class);
        $field_1->shouldReceive('getId')->andReturn(1);
        $field_1->shouldReceive('userCanUpdate')->andReturn(true);

        $field_2 = Mockery::mock(\Tracker_FormElement_Field_File::class);
        $field_2->shouldReceive('getId')->andReturn(2);
        $field_2->shouldReceive('userCanUpdate')->never();

        $this->artifact->shouldReceive('getTracker')->andReturn($this->tracker);

        $this->form_element_factory->shouldReceive('getUsedFileFields')->andReturn([$field_1, $field_2]);

        $this->frozen_field_detector->shouldReceive('isFieldFrozen')->andReturn(false);

        $result = $this->file_upload_data_provider->getFileUploadData($this->tracker, $this->artifact, $this->user);

        $this->assertEquals(1, $result->getField()->getId());
        $this->assertEquals('/api/v1/tracker_fields/1/files', $result->getUploadUrl());
    }

    public function testGetFileUploadDataReturnNullIfFieldFrozen(): void
    {
        $file_1 = Mockery::mock(Tracker_FileInfo::class);
        $file_1->shouldReceive('getId')->andReturn(12);

        $field_1 = Mockery::mock(\Tracker_FormElement_Field_File::class);
        $field_1->shouldReceive('getId')->andReturn(1);
        $field_1->shouldReceive('userCanUpdate')->andReturn(true);

        $this->artifact->shouldReceive('getTracker')->andReturn($this->tracker);

        $this->form_element_factory->shouldReceive('getUsedFileFields')->andReturn([$field_1]);

        $this->frozen_field_detector->shouldReceive('isFieldFrozen')->andReturn(true);

        $result = $this->file_upload_data_provider->getFileUploadData($this->tracker, $this->artifact, $this->user);

        $this->assertNull($result);
    }

    public function testGetFileUploadDataReturnNullIfUserCannotUpdate(): void
    {
        $file_1 = Mockery::mock(Tracker_FileInfo::class);
        $file_1->shouldReceive('getId')->andReturn(12);

        $field_1 = Mockery::mock(\Tracker_FormElement_Field_File::class);
        $field_1->shouldReceive('getId')->andReturn(1);
        $field_1->shouldReceive('userCanUpdate')->andReturn(false);

        $this->artifact->shouldReceive('getTracker')->andReturn($this->tracker);

        $this->form_element_factory->shouldReceive('getUsedFileFields')->andReturn([$field_1]);

        $this->frozen_field_detector->shouldReceive('isFieldFrozen')->never();

        $result = $this->file_upload_data_provider->getFileUploadData(
            $this->tracker,
            $this->artifact,
            $this->user
        );

        $this->assertNull($result);
    }
}
