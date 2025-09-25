<?php
/**
 * Copyright (c) Enalean, 2012 - present. All Rights Reserved.
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

namespace Tuleap\AgileDashboard\Planning;

use AgileDashboard_Milestone_MilestoneStatusCounter;
use PFUser;
use PHPUnit\Framework\MockObject\MockObject;
use Planning_MilestoneFactory;
use Planning_VirtualTopMilestone;
use PlanningFactory;
use PlanningPermissionsManager;
use Psr\Log\NullLogger;
use Tracker_ArtifactFactory;
use Tracker_FormElementFactory;
use Tuleap\AgileDashboard\Milestone\MilestoneDao;
use Tuleap\AgileDashboard\Test\Builders\PlanningBuilder;
use Tuleap\Date\DatePeriodWithOpenDays;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\Test\Builders\ArtifactTestBuilder;
use Tuleap\Tracker\Test\Builders\ChangesetTestBuilder;
use Tuleap\Tracker\Test\Builders\Fields\DateFieldBuilder;
use Tuleap\Tracker\Test\Builders\Fields\IntegerFieldBuilder;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;
use Tuleap\Tracker\Test\Stub\Semantic\Timeframe\BuildSemanticTimeframeStub;
use Tuleap\Tracker\Test\Stub\Semantic\Timeframe\IComputeTimeframesStub;
use Tuleap\Tracker\Tracker;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class MilestoneFactoryGetTopMilestonesTest extends TestCase
{
    private Planning_MilestoneFactory $milestone_factory;
    private Tracker_ArtifactFactory&MockObject $artifact_factory;
    private Planning_VirtualTopMilestone&MockObject $top_milestone;
    private PFUser $user;
    private Tracker $tracker;

    #[\Override]
    protected function setUp(): void
    {
        $planning_factory       = $this->createMock(PlanningFactory::class);
        $this->artifact_factory = $this->createMock(Tracker_ArtifactFactory::class);
        $form_element_factory   = $this->createMock(Tracker_FormElementFactory::class);
        $form_element_factory->method('getComputableFieldByNameForUser');

        $this->milestone_factory = new Planning_MilestoneFactory(
            $planning_factory,
            $this->artifact_factory,
            $form_element_factory,
            $this->createMock(AgileDashboard_Milestone_MilestoneStatusCounter::class),
            $this->createMock(PlanningPermissionsManager::class),
            $this->createMock(MilestoneDao::class),
            BuildSemanticTimeframeStub::withTimeframeCalculator(
                TrackerTestBuilder::aTracker()->build(),
                IComputeTimeframesStub::fromStartAndDuration(
                    DatePeriodWithOpenDays::buildFromDuration(1, 1),
                    DateFieldBuilder::aDateField(1)->build(),
                    IntegerFieldBuilder::anIntField(2)->build(),
                )
            ),
            new NullLogger(),
        );

        $this->tracker = TrackerTestBuilder::aTracker()->withId(12)->withName('tracker')->build();
        $this->user    = UserTestBuilder::anActiveUser()->build();

        $this->top_milestone = $this->createMock(Planning_VirtualTopMilestone::class);
        $this->top_milestone->method('getPlanning')->willReturn(
            PlanningBuilder::aPlanning(3233)
                ->withMilestoneTracker(TrackerTestBuilder::aTracker()->withId(45)->build())
                ->build()
        );
        $this->top_milestone->method('getProject')->willReturn(
            ProjectTestBuilder::aProject()->withId(3233)->build()
        );

        $planning_factory->method('getRootPlanning')->willReturn(PlanningBuilder::aPlanning(3233)->build());
    }

    public function testItReturnsEmptyArrayWhenNoTopMilestonesExist(): void
    {
        $this->artifact_factory->method('getArtifactsByTrackerId')->willReturn([]);
        self::assertEmpty($this->milestone_factory->getSubMilestones($this->user, $this->top_milestone));
    }

    public function testItReturnsMilestonePerArtifact(): void
    {
        $artifact_1 = ArtifactTestBuilder::anArtifact(1)
            ->inTracker($this->tracker)
            ->withChangesets(ChangesetTestBuilder::aChangeset(1)->build())
            ->userCanView($this->user)
            ->withParent(null)
            ->isOpen(true)
            ->withAncestors([])
            ->build();
        $artifact_2 = ArtifactTestBuilder::anArtifact(2)
            ->inTracker($this->tracker)
            ->withChangesets(ChangesetTestBuilder::aChangeset(2)->build())
            ->userCanView($this->user)
            ->withParent(null)
            ->isOpen(true)
            ->withAncestors([])
            ->build();

        $this->artifact_factory->method('getArtifactsByTrackerId')->willReturn([$artifact_1, $artifact_2]);

        $milestones = $this->milestone_factory->getSubMilestones($this->user, $this->top_milestone);
        self::assertCount(2, $milestones);
        self::assertEquals($artifact_1, $milestones[0]->getArtifact());
        self::assertEquals($artifact_2, $milestones[1]->getArtifact());
    }

    public function testItSkipsArtifactsWithoutChangeset(): void
    {
        // Some artifacts have no changeset on Tuleap.net (because of anonymous that can create
        // artifacts but artifact creation fails because they have to write access to fields
        // the artifact creation is stopped half the way hence without changeset
        $artifact_1 = $this->createMock(Artifact::class);
        $artifact_1->method('getLastChangeset')->willReturn(null);
        $artifact_1->method('getTracker')->willReturn($this->tracker);

        $artifact_2 = ArtifactTestBuilder::anArtifact(2)
            ->inTracker($this->tracker)
            ->withChangesets(ChangesetTestBuilder::aChangeset(1)->build())
            ->userCanView($this->user)
            ->withParent(null)
            ->isOpen(true)
            ->withAncestors([])
            ->build();

        $this->artifact_factory->method('getArtifactsByTrackerId')->willReturn([$artifact_1, $artifact_2]);

        $milestones = $this->milestone_factory->getSubMilestones($this->user, $this->top_milestone);
        self::assertCount(1, $milestones);
        self::assertEquals($artifact_2, $milestones[0]->getArtifact());
    }
}
