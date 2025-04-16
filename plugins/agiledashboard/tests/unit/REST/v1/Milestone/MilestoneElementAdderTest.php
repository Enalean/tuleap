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

namespace Tuleap\AgileDashboard\REST\v1\Milestone;

use PHPUnit\Framework\MockObject\MockObject;
use Tracker_ArtifactFactory;
use Tuleap\AgileDashboard\ExplicitBacklog\ExplicitBacklogDao;
use Tuleap\AgileDashboard\ExplicitBacklog\UnplannedArtifactsAdder;
use Tuleap\AgileDashboard\Milestone\Backlog\NoRootPlanningException;
use Tuleap\AgileDashboard\Milestone\Backlog\ProvidedAddedIdIsNotInPartOfTopBacklogException;
use Tuleap\AgileDashboard\Milestone\Backlog\TopBacklogElementsToAddChecker;
use Tuleap\AgileDashboard\REST\v1\BacklogAddRepresentation;
use Tuleap\AgileDashboard\REST\v1\ResourcesPatcher;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\DB\DBTransactionExecutorPassthrough;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Tracker\Test\Builders\ArtifactTestBuilder;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class MilestoneElementAdderTest extends TestCase
{
    private ExplicitBacklogDao&MockObject $explicit_backlog_dao;
    private MilestoneElementAdder $adder;
    private ResourcesPatcher&MockObject $resources_patcher;
    private BacklogAddRepresentation $backlog_add_representation;
    private TopBacklogElementsToAddChecker&MockObject $top_backlog_elements_to_add_checker;
    private Tracker_ArtifactFactory&MockObject $artifact_factory;
    private UnplannedArtifactsAdder&MockObject $unplanned_artifact_adder;

    protected function setUp(): void
    {
        $this->resources_patcher                   = $this->createMock(ResourcesPatcher::class);
        $this->explicit_backlog_dao                = $this->createMock(ExplicitBacklogDao::class);
        $this->unplanned_artifact_adder            = $this->createMock(UnplannedArtifactsAdder::class);
        $this->top_backlog_elements_to_add_checker = $this->createMock(TopBacklogElementsToAddChecker::class);
        $this->artifact_factory                    = $this->createMock(Tracker_ArtifactFactory::class);

        $transaction_executor = new DBTransactionExecutorPassthrough();

        $this->adder = new MilestoneElementAdder(
            $this->explicit_backlog_dao,
            $this->unplanned_artifact_adder,
            $this->resources_patcher,
            $this->top_backlog_elements_to_add_checker,
            $this->artifact_factory,
            $transaction_executor
        );

        $this->backlog_add_representation     = new BacklogAddRepresentation();
        $this->backlog_add_representation->id = 112;
    }

    public function testItAddsElementToMilestoneInExplicitMode(): void
    {
        $user    = UserTestBuilder::buildWithDefaults();
        $add     = [$this->backlog_add_representation];
        $project = ProjectTestBuilder::aProject()->withId(102)->build();

        $artifact = ArtifactTestBuilder::anArtifact(1)->inTracker(TrackerTestBuilder::aTracker()->withId(101)->build())->build();
        $this->artifact_factory->method('getArtifactByIdUserCanView')->willReturn($artifact);

        $this->top_backlog_elements_to_add_checker->expects($this->once())->method('checkAddedIdsBelongToTheProjectTopBacklogTrackers');

        $this->unplanned_artifact_adder->expects($this->once())->method('addArtifactToTopBacklogFromIds');
        $this->resources_patcher->expects($this->once())->method('removeArtifactFromSource');

        $this->explicit_backlog_dao->expects($this->once())->method('isProjectUsingExplicitBacklog')
            ->with(102)
            ->willReturn(true);

        $this->adder->addElementToBacklog($project, $add, $user);
    }

    public function testItAddsElementToMilestoneInStandardMode(): void
    {
        $user    = UserTestBuilder::buildWithDefaults();
        $add     = [$this->backlog_add_representation];
        $project = ProjectTestBuilder::aProject()->withId(102)->build();

        $artifact = ArtifactTestBuilder::anArtifact(1)->inTracker(TrackerTestBuilder::aTracker()->withId(101)->build())->build();
        $this->artifact_factory->method('getArtifactByIdUserCanView')->willReturn($artifact);

        $this->top_backlog_elements_to_add_checker->expects($this->once())->method('checkAddedIdsBelongToTheProjectTopBacklogTrackers');

        $this->explicit_backlog_dao->expects($this->once())->method('isProjectUsingExplicitBacklog')
            ->with(102)
            ->willReturn(false);

        $this->resources_patcher->expects($this->once())->method('removeArtifactFromSource')->with($user, $add);

        $this->adder->addElementToBacklog($project, $add, $user);
    }

    public function testDoesNothingWhenUserCannotSeeTheArtifactInExplicitMode(): void
    {
        $user    = UserTestBuilder::buildWithDefaults();
        $add     = [$this->backlog_add_representation];
        $project = ProjectTestBuilder::aProject()->withId(102)->build();

        $this->artifact_factory->method('getArtifactByIdUserCanView')->willReturn(null);

        $this->top_backlog_elements_to_add_checker->expects($this->never())->method('checkAddedIdsBelongToTheProjectTopBacklogTrackers');
        $this->unplanned_artifact_adder->expects($this->never())->method('addArtifactToTopBacklogFromIds');
        $this->resources_patcher->expects($this->never())->method('removeArtifactFromSource');
        $this->explicit_backlog_dao->expects($this->never())->method('isProjectUsingExplicitBacklog');

        $this->adder->addElementToBacklog($project, $add, $user);
    }

    public function testItDoesNotAddElementToMilestoneIfAtLeastOneArtifactIsNotInTopBacklogTracker(): void
    {
        $user    = UserTestBuilder::buildWithDefaults();
        $add     = [$this->backlog_add_representation];
        $project = ProjectTestBuilder::aProject()->withId(102)->build();

        $artifact = ArtifactTestBuilder::anArtifact(1)->inTracker(TrackerTestBuilder::aTracker()->withId(199)->build())->build();
        $this->artifact_factory->method('getArtifactByIdUserCanView')->willReturn($artifact);

        $this->top_backlog_elements_to_add_checker->expects($this->once())->method('checkAddedIdsBelongToTheProjectTopBacklogTrackers')
            ->willThrowException(new ProvidedAddedIdIsNotInPartOfTopBacklogException([]));

        $this->unplanned_artifact_adder->expects($this->never())->method('addArtifactToTopBacklogFromIds');
        $this->resources_patcher->expects($this->never())->method('removeArtifactFromSource');
        $this->explicit_backlog_dao->expects($this->never())->method('isProjectUsingExplicitBacklog');

        self::expectException(ProvidedAddedIdIsNotInPartOfTopBacklogException::class);

        $this->adder->addElementToBacklog($project, $add, $user);
    }

    public function testItDoesNotAddElementToMilestoneIfNoRootPlanning(): void
    {
        $user    = UserTestBuilder::buildWithDefaults();
        $add     = [$this->backlog_add_representation];
        $project = ProjectTestBuilder::aProject()->withId(102)->build();

        $artifact = ArtifactTestBuilder::anArtifact(1)->inTracker(TrackerTestBuilder::aTracker()->withId(199)->build())->build();
        $this->artifact_factory->method('getArtifactByIdUserCanView')->willReturn($artifact);

        $this->top_backlog_elements_to_add_checker->expects($this->once())->method('checkAddedIdsBelongToTheProjectTopBacklogTrackers')
            ->willThrowException(new NoRootPlanningException());

        $this->unplanned_artifact_adder->expects($this->never())->method('addArtifactToTopBacklogFromIds');
        $this->resources_patcher->expects($this->never())->method('removeArtifactFromSource');
        $this->explicit_backlog_dao->expects($this->never())->method('isProjectUsingExplicitBacklog');

        self::expectException(NoRootPlanningException::class);

        $this->adder->addElementToBacklog($project, $add, $user);
    }
}
