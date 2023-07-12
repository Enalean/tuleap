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

declare(strict_types=1);

namespace Tuleap\Tracker\Artifact\ArtifactsDeletion;

use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use ProjectHistoryDao;
use Psr\EventDispatcher\EventDispatcherInterface;
use Tracker_ArtifactDao;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Tracker\Artifact\Event\ArtifactDeleted;
use Tuleap\Tracker\Test\Builders\ArtifactTestBuilder;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;

final class ArtifactDeletorTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use MockeryPHPUnitIntegration;

    public function testArtifactBecomePendingDeletionOnDelete(): void
    {
        $artifact_id = 101;

        $dao                          = Mockery::mock(Tracker_ArtifactDao::class);
        $project_history_dao          = Mockery::mock(ProjectHistoryDao::class);
        $pending_artifact_removal_dao = Mockery::mock(PendingArtifactRemovalDao::class);
        $artifact_runnner             = Mockery::mock(AsynchronousArtifactsDeletionActionsRunner::class);
        $event_dispatcher             = Mockery::mock(EventDispatcherInterface::class);

        $artifact_deletor = new ArtifactDeletor(
            $dao,
            $project_history_dao,
            $pending_artifact_removal_dao,
            $artifact_runnner,
            $event_dispatcher
        );

        $project = ProjectTestBuilder::aProject()->withId(104)->build();
        $tracker = TrackerTestBuilder::aTracker()->withName("My tracker name")->withProject($project)->build();

        $artifact = ArtifactTestBuilder::anArtifact($artifact_id)->inTracker($tracker)->build();
        $user     = UserTestBuilder::anActiveUser()->withId(110)->build();

        $dao->shouldReceive("startTransaction");
        $pending_artifact_removal_dao->shouldReceive("addArtifactToPendingRemoval")->withArgs([$artifact_id]);
        $dao->shouldReceive("delete")->withArgs([$artifact_id]);
        $dao->shouldReceive("commit");

        $context = DeletionContext::regularDeletion((int) $project->getID());
        $artifact_runnner->shouldReceive("executeArchiveAndArtifactDeletion")->withArgs([$artifact, $user, $context]);

        $project_history_dao->shouldReceive("groupAddHistory");

        $event_dispatcher->shouldReceive('dispatch')->with(ArtifactDeleted::class)->once();

        $artifact_deletor->delete($artifact, $user, $context);
    }
}
