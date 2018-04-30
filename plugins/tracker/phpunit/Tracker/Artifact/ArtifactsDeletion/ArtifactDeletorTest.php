<?php
/**
 * Copyright (c) Enalean, 2018. All Rights Reserved.
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

namespace Tuleap\Tracker\Artifact\ArtifactsDeletion;

require_once __DIR__ . '/../../../bootstrap.php';

use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Tracker_FormElement_Field_ComputedDaoCache;
use Tuleap\Tracker\Artifact\ArtifactWithTrackerStructureExporter;
use Tuleap\Tracker\RecentlyVisited\RecentlyVisitedDao;

class ArtifactDeletorTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    protected function setUp()
    {
        parent::setUp();
        \ForgeConfig::store();
        \ForgeConfig::set('tmp_dir', '/do_not_exist_nothing_will_be_written_during_the_test');
    }

    protected function tearDown()
    {
        \ForgeConfig::restore();
        parent::tearDown();
    }

    public function testArtifactIsDeleted()
    {
        $user        = Mockery::mock(\PFUser::class);
        $changesets  = [
            Mockery::mock(\Tracker_Artifact_Changeset::class),
            Mockery::mock(\Tracker_Artifact_Changeset::class),
            Mockery::mock(\Tracker_Artifact_Changeset::class)
        ];
        foreach ($changesets as $changeset) {
            $changeset->shouldReceive('delete')->with($user)->once();
        }

        $artifact = Mockery::mock(\Tracker_Artifact::class);
        $artifact->shouldReceive('getChangesets')->andReturn($changesets);
        $artifact->shouldReceive('getId');

        $tracker = Mockery::mock(\Tracker::class);
        $tracker->shouldReceive('getGroupId');
        $tracker->shouldReceive('getName');
        $artifact->shouldReceive('getTracker')->andReturn($tracker);
        $artifact->shouldReceive('getTrackerId');

        $artifact_dao = Mockery::mock(\Tracker_ArtifactDao::class);
        $artifact_dao->shouldReceive('startTransaction')->once();
        $artifact_dao->shouldReceive('deleteArtifactLinkReference')->once();
        $artifact_dao->shouldReceive('deleteUnsubscribeNotificationForArtifact')->once();
        $artifact_dao->shouldReceive('delete')->once();
        $artifact_dao->shouldReceive('commit')->once();

        $permissions_manager = Mockery::mock(\PermissionsManager::class);
        $permissions_manager->shouldReceive('clearPermission');

        $cross_reference_manager = Mockery::mock(\CrossReferenceManager::class);
        $cross_reference_manager->shouldReceive('deleteEntity');

        $tracker_artifact_priority_manager = Mockery::mock(\Tracker_Artifact_PriorityManager::class);
        $tracker_artifact_priority_manager->shouldReceive('deletePriority');

        $project_history_dao = Mockery::mock(\ProjectHistoryDao::class);
        $project_history_dao->shouldReceive('groupAddHistory')->once();

        $event_manager = Mockery::mock(\EventManager::class);
        $event_manager->shouldReceive('processEvent')->twice();

        $artifact_with_tracker_structure_exporter = Mockery::mock(ArtifactWithTrackerStructureExporter::class);
        $artifact_with_tracker_structure_exporter->shouldReceive('exportArtifactAndTrackerStructureToXML');

        $computed_cache_dao = Mockery::mock(Tracker_FormElement_Field_ComputedDaoCache::class);
        $computed_cache_dao->shouldReceive('deleteAllArtifactCacheValues')->once();

        $recently_visited_dao = Mockery::mock(RecentlyVisitedDao::class);
        $recently_visited_dao->shouldReceive('deleteVisitByArtifactId')->once();

        $artifact_deletor = new ArtifactDeletor(
            $artifact_dao,
            $permissions_manager,
            $cross_reference_manager,
            $tracker_artifact_priority_manager,
            $project_history_dao,
            $event_manager,
            $artifact_with_tracker_structure_exporter,
            $computed_cache_dao,
            $recently_visited_dao
        );

        $artifact_deletor->delete($artifact, $user);
    }
}
