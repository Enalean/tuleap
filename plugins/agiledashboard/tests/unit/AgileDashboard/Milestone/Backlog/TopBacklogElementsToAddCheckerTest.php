<?php
/**
 * Copyright (c) Enalean, 2019 - present. All Rights Reserved.
 *
 *  This file is a part of Tuleap.
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
 *
 */

declare(strict_types=1);

namespace Tuleap\AgileDashboard\Milestone\Backlog;

use PFUser;
use PHPUnit\Framework\MockObject\MockObject;
use Planning;
use Project;
use Tuleap\AgileDashboard\Planning\RetrieveRootPlanning;
use Tuleap\AgileDashboard\Test\Builders\PlanningBuilder;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\Test\Builders\ArtifactTestBuilder;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;
use Tuleap\Tracker\Test\Stub\RetrieveArtifactStub;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class TopBacklogElementsToAddCheckerTest extends TestCase
{
    private readonly TopBacklogElementsToAddChecker $checker;
    private readonly RetrieveRootPlanning&MockObject $planning_factory;
    private readonly Project $project;
    private readonly PFUser $user;
    private readonly Planning $root_planning;
    private readonly Artifact $artifact_201;

    protected function setUp(): void
    {
        $tracker_104        = TrackerTestBuilder::aTracker()->withId(104)->build();
        $tracker_101        = TrackerTestBuilder::aTracker()->withId(101)->build();
        $this->artifact_201 = ArtifactTestBuilder::anArtifact(201)
            ->inTracker($tracker_104)
            ->build();
        $artifact_202       = ArtifactTestBuilder::anArtifact(202)
            ->inTracker($tracker_101)
            ->build();

        $this->planning_factory = $this->createMock(RetrieveRootPlanning::class);
        $this->checker          = new TopBacklogElementsToAddChecker(
            $this->planning_factory,
            RetrieveArtifactStub::withArtifacts($this->artifact_201, $artifact_202)
        );

        $this->project = ProjectTestBuilder::aProject()->withId(101)->build();
        $this->user    = UserTestBuilder::buildWithDefaults();

        $this->root_planning = PlanningBuilder::aPlanning(101)
            ->withBacklogTrackers($tracker_101, $tracker_104)->build();
    }

    public function testItDoesNotThrowExceptionIfArtifactsAreInTopBacklogTracker(): void
    {
        $this->planning_factory->method('getRootPlanning')
            ->with($this->user, 101)
            ->willReturn($this->root_planning);

        $added_artifact_ids = [201, 202];

        $this->checker->checkAddedIdsBelongToTheProjectTopBacklogTrackers(
            $this->project,
            $this->user,
            $added_artifact_ids
        );
    }

    public function testItThrowsAnExceptionIfNoRootPlanning(): void
    {
        $this->planning_factory->method('getRootPlanning')
            ->with($this->user, 101)
            ->willReturn(false);

        $this->expectException(NoRootPlanningException::class);

        $this->checker->checkAddedIdsBelongToTheProjectTopBacklogTrackers(
            $this->project,
            $this->user,
            []
        );
    }

    public function testItThrowsAnExceptionIfAtLeastOneArtifactIsNotInTopBacklogTracker(): void
    {
        $this->planning_factory->method('getRootPlanning')
            ->with($this->user, 101)
            ->willReturn($this->root_planning);

        $this->artifact_201->setTracker(TrackerTestBuilder::aTracker()->withId(999)->build());

        $this->expectException(ProvidedAddedIdIsNotInPartOfTopBacklogException::class);

        $added_artifact_ids = [201, 202];

        $this->checker->checkAddedIdsBelongToTheProjectTopBacklogTrackers(
            $this->project,
            $this->user,
            $added_artifact_ids
        );
    }
}
