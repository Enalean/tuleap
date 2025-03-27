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

use ProjectHistoryDao;
use Psr\EventDispatcher\EventDispatcherInterface;
use Tracker_ArtifactDao;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Tracker\Artifact\Event\ArtifactDeleted;
use Tuleap\Tracker\Test\Builders\ArtifactTestBuilder;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class ArtifactDeletorTest extends TestCase
{
    public function testArtifactBecomePendingDeletionOnDelete(): void
    {
        $artifact_id = 101;

        $dao                          = $this->createMock(Tracker_ArtifactDao::class);
        $project_history_dao          = $this->createMock(ProjectHistoryDao::class);
        $pending_artifact_removal_dao = $this->createMock(PendingArtifactRemovalDao::class);
        $artifact_runnner             = $this->createMock(AsynchronousArtifactsDeletionActionsRunner::class);
        $event_dispatcher             = $this->createMock(EventDispatcherInterface::class);

        $artifact_deletor = new ArtifactDeletor(
            $dao,
            $project_history_dao,
            $pending_artifact_removal_dao,
            $artifact_runnner,
            $event_dispatcher
        );

        $project = ProjectTestBuilder::aProject()->withId(104)->build();
        $tracker = TrackerTestBuilder::aTracker()->withName('My tracker name')->withProject($project)->build();

        $artifact = ArtifactTestBuilder::anArtifact($artifact_id)->inTracker($tracker)->build();
        $user     = UserTestBuilder::anActiveUser()->withId(110)->build();

        $dao->method('startTransaction');
        $pending_artifact_removal_dao->method('addArtifactToPendingRemoval')->with($artifact_id);
        $dao->method('delete')->with($artifact_id);
        $dao->method('commit');

        $context = DeletionContext::regularDeletion((int) $project->getID());
        $artifact_runnner->method('executeArchiveAndArtifactDeletion')->with($artifact, $user, $context);

        $project_history_dao->method('groupAddHistory');

        $event_dispatcher->expects($this->once())->method('dispatch')->with(self::isInstanceOf(ArtifactDeleted::class));

        $artifact_deletor->delete($artifact, $user, $context);
    }
}
