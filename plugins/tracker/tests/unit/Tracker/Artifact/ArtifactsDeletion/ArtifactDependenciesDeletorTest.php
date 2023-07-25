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

use PermissionsManager;
use Tracker_Artifact_ChangesetValue_File;
use Tracker_Artifact_PriorityManager;
use Tracker_ArtifactDao;
use Tracker_FileInfo;
use Tuleap\Reference\CrossReferenceManager;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\Artifact\RecentlyVisited\RecentlyVisitedDao;
use Tuleap\Tracker\FormElement\Field\Computed\ComputedFieldDaoCache;
use Tuleap\Tracker\Test\Builders\ArtifactTestBuilder;
use Tuleap\Tracker\Test\Builders\TrackerFormElementFileFieldBuilder;

final class ArtifactDependenciesDeletorTest extends TestCase
{
    private const PROJECT_ID = 109;
    private CrossReferenceManager|\PHPUnit\Framework\MockObject\Stub $cross_reference_manager;
    private ArtifactDependenciesDeletor $deletor;
    private \Tracker|\PHPUnit\Framework\MockObject\Stub $tracker;
    private \Tracker_FormElement_Field_File $file_field;

    protected function setUp(): void
    {
        $permissions_manager               = $this->createStub(PermissionsManager::class);
        $this->cross_reference_manager     = $this->createStub(CrossReferenceManager::class);
        $tracker_artifact_priority_manager = $this->createStub(Tracker_Artifact_PriorityManager::class);
        $dao                               = $this->createStub(Tracker_ArtifactDao::class);
        $computed_dao_cache                = $this->createStub(ComputedFieldDaoCache::class);
        $recently_visited_dao              = $this->createStub(RecentlyVisitedDao::class);
        $artifact_removal                  = $this->createStub(PendingArtifactRemovalDao::class);

        $permissions_manager->expects(self::atLeastOnce())->method('clearPermission');
        $dao->expects(self::atLeastOnce())->method('deleteArtifactLinkReference');
        $dao->expects(self::atLeastOnce())->method('deleteUnsubscribeNotificationForArtifact');
        $tracker_artifact_priority_manager->expects(self::atLeastOnce())->method('deletePriority');
        $computed_dao_cache->expects(self::atLeastOnce())->method('deleteAllArtifactCacheValues');
        $recently_visited_dao->expects(self::atLeastOnce())->method('deleteVisitByArtifactId');
        $artifact_removal->expects(self::atLeastOnce())->method('removeArtifact');

        $this->deletor = new ArtifactDependenciesDeletor(
            $permissions_manager,
            $this->cross_reference_manager,
            $tracker_artifact_priority_manager,
            $dao,
            $computed_dao_cache,
            $recently_visited_dao,
            $artifact_removal,
        );

        $this->tracker    = $this->createStub(\Tracker::class);
        $this->file_field = TrackerFormElementFileFieldBuilder::aFileField(1)->build();
        $this->tracker->method('getFormElementFields')->willReturn([$this->file_field]);
        $this->tracker->method('getID')->willReturn(12);
        $this->tracker->method('getGroupId')->willReturn(self::PROJECT_ID);
    }

    public function testItCleanDependenciesForRegularDeletion(): void
    {
        $artifact = $this->createStub(Artifact::class);
        $artifact->method('getId')->willReturn(1);
        $artifact->method('getTracker')->willReturn($this->tracker);
        $file_changeset_value = $this->createStub(Tracker_Artifact_ChangesetValue_File::class);
        $file                 = $this->createStub(Tracker_FileInfo::class);
        $file_changeset_value->method('getFiles')->willReturn([$file]);
        $file->expects(self::once())->method('deleteFiles');

        $artifact->method('getValue')->with($this->file_field)->willReturn($file_changeset_value);
        $this->cross_reference_manager->expects(self::never())->method("deleteReferencesWhenArtifactIsSource");
        $this->cross_reference_manager->expects(self::never())->method("updateReferencesWhenArtifactIsInTarget");
        $this->cross_reference_manager->expects(self::once())->method('deleteEntity');
        $this->deletor->cleanDependencies($artifact, DeletionContext::regularDeletion(self::PROJECT_ID));
    }

    public function testSourceReferencesAreDeletedForMoveArtifact(): void
    {
        $artifact = ArtifactTestBuilder::anArtifact(1)->inTracker($this->tracker)->build();
        $this->cross_reference_manager->expects(self::once())->method("deleteReferencesWhenArtifactIsSource");
        $this->cross_reference_manager->expects(self::once())->method("updateReferencesWhenArtifactIsInTarget");
        $this->cross_reference_manager->expects(self::never())->method('deleteEntity');
        $this->deletor->cleanDependencies($artifact, DeletionContext::moveContext(self::PROJECT_ID, 123456));
    }
}
