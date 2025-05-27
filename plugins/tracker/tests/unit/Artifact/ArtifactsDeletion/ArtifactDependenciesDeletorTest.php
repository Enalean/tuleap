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
use Tracker_ArtifactDao;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Tracker\Artifact\RecentlyVisited\RecentlyVisitedDao;
use Tuleap\Tracker\Artifact\PriorityManager;
use Tuleap\Tracker\FormElement\Field\Computed\ComputedFieldDaoCache;
use Tuleap\Tracker\Test\Builders\ArtifactTestBuilder;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class ArtifactDependenciesDeletorTest extends TestCase
{
    private const PROJECT_ID = 109;
    private ArtifactDependenciesCleaner $deletor;
    private \Tracker|\PHPUnit\Framework\MockObject\Stub $tracker;
    private \Tracker_FormElement_Field_File $file_field;
    private \PFUser $user;
    private \PHPUnit\Framework\MockObject\MockObject&PostArtifactMoveReferencesCleaner $post_move_deletor;
    private PostArtifactDeletionCleaner&\PHPUnit\Framework\MockObject\MockObject $artifact_deletor;

    protected function setUp(): void
    {
        $permissions_manager               = $this->createMock(PermissionsManager::class);
        $tracker_artifact_priority_manager = $this->createMock(PriorityManager::class);
        $dao                               = $this->createMock(Tracker_ArtifactDao::class);
        $computed_dao_cache                = $this->createMock(ComputedFieldDaoCache::class);
        $recently_visited_dao              = $this->createMock(RecentlyVisitedDao::class);
        $artifact_removal                  = $this->createMock(PendingArtifactRemovalDao::class);
        $this->post_move_deletor           = $this->createMock(PostArtifactMoveReferencesCleaner::class);
        $this->artifact_deletor            = $this->createMock(PostArtifactDeletionCleaner::class);

        $permissions_manager->expects($this->atLeastOnce())->method('clearPermission');
        $dao->expects($this->atLeastOnce())->method('deleteUnsubscribeNotificationForArtifact');
        $tracker_artifact_priority_manager->expects($this->atLeastOnce())->method('deletePriority');
        $computed_dao_cache->expects($this->atLeastOnce())->method('deleteAllArtifactCacheValues');
        $recently_visited_dao->expects($this->atLeastOnce())->method('deleteVisitByArtifactId');
        $artifact_removal->expects($this->atLeastOnce())->method('removeArtifact');

        $this->deletor = new ArtifactDependenciesCleaner(
            $permissions_manager,
            $tracker_artifact_priority_manager,
            $dao,
            $computed_dao_cache,
            $recently_visited_dao,
            $artifact_removal,
            $this->post_move_deletor,
            $this->artifact_deletor
        );

        $this->tracker = TrackerTestBuilder::aTracker()->build();
        $this->user    = UserTestBuilder::anActiveUser()->build();
    }

    public function testItCleanDependenciesForRegularDeletion(): void
    {
        $artifact = ArtifactTestBuilder::anArtifact(1)->inTracker($this->tracker)->build();

        $this->post_move_deletor->expects($this->never())->method('cleanReferencesAfterArtifactMove');
        $this->artifact_deletor->expects($this->once())->method('cleanReferencesAfterSimpleArtifactDeletion');
        $this->deletor->cleanDependencies($artifact, DeletionContext::regularDeletion(self::PROJECT_ID), $this->user);
    }

    public function testSourceReferencesAreDeletedForMoveArtifact(): void
    {
        $artifact = ArtifactTestBuilder::anArtifact(1)->inTracker($this->tracker)->build();

        $this->post_move_deletor->expects($this->once())->method('cleanReferencesAfterArtifactMove');
        $this->artifact_deletor->expects($this->never())->method('cleanReferencesAfterSimpleArtifactDeletion');
        $this->deletor->cleanDependencies($artifact, DeletionContext::moveContext(self::PROJECT_ID, 123456), $this->user);
    }
}
