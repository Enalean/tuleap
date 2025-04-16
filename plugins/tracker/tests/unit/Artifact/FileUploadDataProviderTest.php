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

use PFUser;
use PHPUnit\Framework\MockObject\MockObject;
use Tracker;
use Tracker_FileInfo;
use Tracker_FormElementFactory;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Tracker\Test\Builders\ArtifactTestBuilder;
use Tuleap\Tracker\Test\Builders\Fields\FileFieldBuilder;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;
use Tuleap\Tracker\Workflow\PostAction\FrozenFields\FrozenFieldDetector;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class FileUploadDataProviderTest extends TestCase
{
    private Tracker_FormElementFactory&MockObject $form_element_factory;
    private FrozenFieldDetector&MockObject $frozen_field_detector;
    private FileUploadDataProvider $file_upload_data_provider;
    private Tracker $tracker;
    private Artifact $artifact;
    private PFUser $user;

    protected function setUp(): void
    {
        $this->form_element_factory  = $this->createMock(Tracker_FormElementFactory::class);
        $this->frozen_field_detector = $this->createMock(FrozenFieldDetector::class);
        $this->tracker               = TrackerTestBuilder::aTracker()->build();
        $this->artifact              = ArtifactTestBuilder::anArtifact(46)->inTracker($this->tracker)->build();
        $this->user                  = UserTestBuilder::buildWithDefaults();

        $this->file_upload_data_provider = new FileUploadDataProvider(
            $this->frozen_field_detector,
            $this->form_element_factory
        );
    }

    public function testGetFileUploadData(): void
    {
        $file_1 = $this->createMock(Tracker_FileInfo::class);
        $file_1->method('getId')->willReturn(12);

        $field_1 = FileFieldBuilder::aFileField(1)->withUpdatePermission($this->user, true)->build();
        $field_2 = FileFieldBuilder::aFileField(2)->build();

        $this->form_element_factory->method('getUsedFileFields')->willReturn([$field_1, $field_2]);

        $this->frozen_field_detector->method('isFieldFrozen')->willReturn(false);

        $result = $this->file_upload_data_provider->getFileUploadData($this->tracker, $this->artifact, $this->user);

        self::assertEquals(1, $result->getField()->getId());
        self::assertEquals('/api/v1/tracker_fields/1/files', $result->getUploadUrl());
    }

    public function testGetFileUploadDataReturnNullIfFieldFrozen(): void
    {
        $file_1 = $this->createMock(Tracker_FileInfo::class);
        $file_1->method('getId')->willReturn(12);

        $field_1 = FileFieldBuilder::aFileField(1)->withUpdatePermission($this->user, true)->build();

        $this->form_element_factory->method('getUsedFileFields')->willReturn([$field_1]);

        $this->frozen_field_detector->method('isFieldFrozen')->willReturn(true);

        $result = $this->file_upload_data_provider->getFileUploadData($this->tracker, $this->artifact, $this->user);

        self::assertNull($result);
    }

    public function testGetFileUploadDataReturnNullIfUserCannotUpdate(): void
    {
        $file_1 = $this->createMock(Tracker_FileInfo::class);
        $file_1->method('getId')->willReturn(12);

        $field_1 = FileFieldBuilder::aFileField(1)->withUpdatePermission($this->user, false)->build();

        $this->form_element_factory->method('getUsedFileFields')->willReturn([$field_1]);

        $this->frozen_field_detector->expects($this->never())->method('isFieldFrozen');

        $result = $this->file_upload_data_provider->getFileUploadData(
            $this->tracker,
            $this->artifact,
            $this->user
        );

        self::assertNull($result);
    }

    public function testGetFileUploadDataForSubmit(): void
    {
        $file_1 = $this->createMock(Tracker_FileInfo::class);
        $file_1->method('getId')->willReturn(12);

        $field_1 = FileFieldBuilder::aFileField(1)->withSubmitPermission($this->user, true)->build();
        $field_2 = FileFieldBuilder::aFileField(2)->build();

        $this->form_element_factory->method('getUsedFileFields')->willReturn([$field_1, $field_2]);

        $this->frozen_field_detector->method('isFieldFrozen')->willReturn(false);

        $result = $this->file_upload_data_provider->getFileUploadDataForSubmit($this->tracker, $this->user);

        self::assertEquals(1, $result->getField()->getId());
        self::assertEquals('/api/v1/tracker_fields/1/files', $result->getUploadUrl());
    }

    public function testGetFileUploadDataForSubmitReturnNullIfUserCannotSubmit(): void
    {
        $file_1 = $this->createMock(Tracker_FileInfo::class);
        $file_1->method('getId')->willReturn(12);

        $field_1 = FileFieldBuilder::aFileField(1)->withSubmitPermission($this->user, false)->build();

        $this->form_element_factory->method('getUsedFileFields')->willReturn([$field_1]);

        $this->frozen_field_detector->expects($this->never())->method('isFieldFrozen');

        $result = $this->file_upload_data_provider->getFileUploadDataForSubmit($this->tracker, $this->user);

        self::assertNull($result);
    }
}
