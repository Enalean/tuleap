<?php
/**
 * Copyright (c) Enalean 2023 - Present. All Rights Reserved.
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

namespace Tuleap\Tracker\Artifact\ArtifactsDeletion;

use Tracker_Artifact_ChangesetValue_File;
use Tracker_FileInfo;
use Tuleap\Reference\CrossReferenceManager;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\Test\Builders\TrackerFormElementFileFieldBuilder;

final class PostArtifactDeletionCleanerTest extends TestCase
{
    private CrossReferenceManager|\PHPUnit\Framework\MockObject\Stub $reference_manager;
    private \Tracker_ArtifactDao|\PHPUnit\Framework\MockObject\Stub $artifact_dao;
    private PostArtifactDeletionCleaner $cleaner;

    protected function setUp(): void
    {
        $this->reference_manager = $this->createStub(CrossReferenceManager::class);
        $this->artifact_dao      = $this->createStub(\Tracker_ArtifactDao::class);

        $this->cleaner = new PostArtifactDeletionCleaner($this->reference_manager, $this->artifact_dao);
    }

    public function testItCleanDependenciesAtArtifactRemoval(): void
    {
        $this->reference_manager->expects(self::once())->method('deleteEntity');
        $this->artifact_dao->expects(self::once())->method('deleteArtifactLinkReference');

        $file_field = TrackerFormElementFileFieldBuilder::aFileField(1)->build();
        $tracker    = $this->createStub(\Tracker::class);
        $tracker->method('getFormElementFields')->willReturn([$file_field]);
        $tracker->method('getId')->willReturn(987);
        $tracker->method('getGroupId')->willReturn(109);

        $artifact = $this->createStub(Artifact::class);
        $artifact->method('getId')->willReturn(1);
        $artifact->method('getTracker')->willReturn($tracker);

        $file_changeset_value = $this->createStub(Tracker_Artifact_ChangesetValue_File::class);
        $file                 = $this->createStub(Tracker_FileInfo::class);
        $file_changeset_value->method('getFiles')->willReturn([$file]);
        $file->expects(self::once())->method('deleteFiles');

        $artifact->method('getValue')->willReturn($file_changeset_value);

        $this->cleaner->cleanReferencesAfterSimpleArtifactDeletion($artifact);
    }
}
