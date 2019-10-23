<?php
/**
 * Copyright (c) Enalean, 2018 - Present. All Rights Reserved.
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

use EventManager;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use ProjectHistoryDao;
use Tracker_ArtifactDao;

class ArtifactDeletorTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    public function testArtifactBecomePendingDeletionOnDelete()
    {
        $artifact_id = 101;

        $dao                          = Mockery::mock(Tracker_ArtifactDao::class);
        $project_history_dao          = Mockery::mock(ProjectHistoryDao::class);
        $pending_artifact_removal_dao = Mockery::mock(PendingArtifactRemovalDao::class);
        $artifact_runnner             = Mockery::mock(AsynchronousArtifactsDeletionActionsRunner::class);
        $event_manager                = Mockery::mock(EventManager::class);

        $artifact_deletor = new ArtifactDeletor(
            $dao,
            $project_history_dao,
            $pending_artifact_removal_dao,
            $artifact_runnner,
            $event_manager
        );

        $tracker = Mockery::mock(\Tracker::class);
        $tracker->shouldReceive('getName')->andReturn("My tracker name");
        $tracker->shouldReceive('getGroupId')->andReturn(104);

        $artifact = Mockery::mock(\Tracker_Artifact::class);
        $artifact->shouldReceive("getId")->andReturn($artifact_id);
        $artifact->shouldReceive('getTrackerId')->andReturn(4);
        $artifact->shouldReceive('getTracker')->andReturn($tracker);

        $user     = Mockery::mock(\PFUser::class);
        $user->shouldReceive('getId')->andReturn(110);

        $dao->shouldReceive("startTransaction");
        $pending_artifact_removal_dao->shouldReceive("addArtifactToPendingRemoval")->withArgs([$artifact_id]);
        $dao->shouldReceive("delete")->withArgs([$artifact_id]);
        $dao->shouldReceive("commit");

        $artifact_runnner->shouldReceive("executeArchiveAndArtifactDeletion")->withArgs([$artifact, $user]);

        $project_history_dao->shouldReceive("groupAddHistory");

        $event_manager->shouldReceive('processEvent')->once();

        $artifact_deletor->delete($artifact, $user);
    }
}
