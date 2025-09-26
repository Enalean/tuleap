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
use Tuleap\Tracker\Test\Builders\ArtifactTestBuilder;
use Tuleap\Tracker\Test\Builders\ChangesetTestBuilder;
use Tuleap\Tracker\Test\Builders\Fields\DateFieldBuilder;
use Tuleap\Tracker\Test\Builders\Fields\IntegerFieldBuilder;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;
use Tuleap\Tracker\Test\Stub\Semantic\Timeframe\BuildSemanticTimeframeStub;
use Tuleap\Tracker\Test\Stub\Semantic\Timeframe\IComputeTimeframesStub;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class MilestoneFactoryGetBareMilestoneByArtifactIdTest extends TestCase
{
    private int $artifact_id;
    private Planning_MilestoneFactory $milestone_factory;
    private PlanningFactory&MockObject $planning_factory;
    private Tracker_ArtifactFactory&MockObject $artifact_factory;
    private PFUser $user;

    #[\Override]
    protected function setUp(): void
    {
        $this->planning_factory = $this->createMock(PlanningFactory::class);
        $this->artifact_factory = $this->createMock(Tracker_ArtifactFactory::class);
        $form_element_factory   = $this->createMock(Tracker_FormElementFactory::class);
        $form_element_factory->method('getComputableFieldByNameForUser');

        $this->milestone_factory = new Planning_MilestoneFactory(
            $this->planning_factory,
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
                ),
            ),
            new NullLogger(),
        );
        $this->user              = UserTestBuilder::anActiveUser()->build();
        $this->artifact_id       = 112;
    }

    public function testItReturnsNullIfArtifactDoesntExist(): void
    {
        $this->artifact_factory->method('getArtifactById');
        self::assertNull($this->milestone_factory->getBareMilestoneByArtifactId($this->user, $this->artifact_id));
    }

    public function testItReturnsAMilestone(): void
    {
        $planning_tracker = TrackerTestBuilder::aTracker()
            ->withId(12)
            ->withProject(ProjectTestBuilder::aProject()->build())
            ->build();

        $this->planning_factory
            ->method('getPlanningByPlanningTracker')
            ->with($this->user, $planning_tracker)
            ->willReturn(PlanningBuilder::aPlanning(101)->build());

        $changeset = ChangesetTestBuilder::aChangeset(1)->build();

        $artifact = ArtifactTestBuilder::anArtifact($this->artifact_id)
            ->withTitle('title')
            ->inTracker($planning_tracker)
            ->withChangesets($changeset)
            ->userCanView($this->user)
            ->withParent(null)
            ->isOpen(true)
            ->withAncestors([])
            ->build();

        $this->artifact_factory->method('getArtifactById')->with($this->artifact_id)->willReturn($artifact);

        $milestone = $this->milestone_factory->getBareMilestoneByArtifactId($this->user, $this->artifact_id);
        self::assertEquals($artifact, $milestone->getArtifact());
    }

    public function testItReturnsNullWhenArtifactIsNotAMilestone(): void
    {
        $planning_tracker = TrackerTestBuilder::aTracker()->build();
        $this->planning_factory->method('getPlanningByPlanningTracker')->willReturn(null);

        $artifact = ArtifactTestBuilder::anArtifact(1)
            ->inTracker($planning_tracker)
            ->userCanView($this->user)
            ->build();
        $this->artifact_factory->method('getArtifactById')->with($this->artifact_id)->willReturn($artifact);

        self::assertNull($this->milestone_factory->getBareMilestoneByArtifactId($this->user, $this->artifact_id));
    }

    public function testItReturnsNullWhenUserCannotSeeArtifacts(): void
    {
        $this->planning_factory->method('getPlanningByPlanningTracker')->willReturn(PlanningBuilder::aPlanning(101)->build());

        $artifact = ArtifactTestBuilder::anArtifact(1)
            ->userCannotView($this->user)
            ->build();
        $this->artifact_factory->method('getArtifactById')->with($this->artifact_id)->willReturn($artifact);

        self::assertNull($this->milestone_factory->getBareMilestoneByArtifactId($this->user, $this->artifact_id));
    }
}
