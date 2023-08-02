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
use Tracker_Artifact_PriorityManager;
use Tracker_ArtifactDao;
use Tuleap\Reference\CrossReferenceManager;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Tracker\Artifact\RecentlyVisited\RecentlyVisitedDao;
use Tuleap\Tracker\FormElement\Field\Computed\ComputedFieldDaoCache;
use Tuleap\Tracker\Test\Builders\ArtifactTestBuilder;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;

final class ArtifactDependenciesDeletorTest extends TestCase
{
    private const PROJECT_ID = 109;
    private CrossReferenceManager|\PHPUnit\Framework\MockObject\Stub $cross_reference_manager;
    private ArtifactDependenciesCleaner $deletor;
    private \Tracker|\PHPUnit\Framework\MockObject\Stub $tracker;
    private \Tracker_FormElement_Field_File $file_field;
    private \PFUser $user;
    private \PHPUnit\Framework\MockObject\Stub|PostArtifactMoveReferencesCleaner $post_move_deletor;
    private PostArtifactDeletionCleaner|\PHPUnit\Framework\MockObject\Stub $artifact_deletor;

    protected function setUp(): void
    {
        $permissions_manager               = $this->createStub(PermissionsManager::class);
        $tracker_artifact_priority_manager = $this->createStub(Tracker_Artifact_PriorityManager::class);
        $dao                               = $this->createStub(Tracker_ArtifactDao::class);
        $computed_dao_cache                = $this->createStub(ComputedFieldDaoCache::class);
        $recently_visited_dao              = $this->createStub(RecentlyVisitedDao::class);
        $artifact_removal                  = $this->createStub(PendingArtifactRemovalDao::class);
        $value_deletor                     = $this->createStub(ArtifactChangesetValueDeletorDAO::class);
        $this->post_move_deletor           = $this->createStub(PostArtifactMoveReferencesCleaner::class);
        $this->artifact_deletor            = $this->createStub(PostArtifactDeletionCleaner::class);

        $permissions_manager->expects(self::atLeastOnce())->method('clearPermission');
        $dao->expects(self::atLeastOnce())->method('deleteUnsubscribeNotificationForArtifact');
        $tracker_artifact_priority_manager->expects(self::atLeastOnce())->method('deletePriority');
        $computed_dao_cache->expects(self::atLeastOnce())->method('deleteAllArtifactCacheValues');
        $recently_visited_dao->expects(self::atLeastOnce())->method('deleteVisitByArtifactId');
        $artifact_removal->expects(self::atLeastOnce())->method('removeArtifact');
        $value_deletor->expects(self::atLeastOnce())->method('cleanAllChangesetValueInTransaction');

        $this->deletor = new ArtifactDependenciesCleaner(
            $permissions_manager,
            $tracker_artifact_priority_manager,
            $dao,
            $computed_dao_cache,
            $recently_visited_dao,
            $artifact_removal,
            $value_deletor,
            $this->post_move_deletor,
            $this->artifact_deletor
        );

        $this->tracker = TrackerTestBuilder::aTracker()->build();
        $this->user    = UserTestBuilder::anActiveUser()->build();
    }

    public function testItCleanDependenciesForRegularDeletion(): void
    {
        $artifact = ArtifactTestBuilder::anArtifact(1)->inTracker($this->tracker)->build();

        $this->post_move_deletor->expects(self::never())->method("cleanReferencesAfterArtifactMove");
        $this->artifact_deletor->expects(self::once())->method("cleanReferencesAfterSimpleArtifactDeletion");
        $this->deletor->cleanDependencies($artifact, DeletionContext::regularDeletion(self::PROJECT_ID), $this->user);
    }

    public function testSourceReferencesAreDeletedForMoveArtifact(): void
    {
        $artifact = ArtifactTestBuilder::anArtifact(1)->inTracker($this->tracker)->build();

        $this->post_move_deletor->expects(self::once())->method("cleanReferencesAfterArtifactMove");
        $this->artifact_deletor->expects(self::never())->method("cleanReferencesAfterSimpleArtifactDeletion");
        $this->deletor->cleanDependencies($artifact, DeletionContext::moveContext(self::PROJECT_ID, 123456), $this->user);
    }
}
