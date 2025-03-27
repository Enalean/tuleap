<?php
/**
 * Copyright (c) Enalean, 2020-Present. All Rights Reserved.
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

namespace Tuleap\AgileDashboard\Planning;

use AgileDashboard_Milestone_MilestoneStatusCounter;
use PFUser;
use PHPUnit\Framework\MockObject\MockObject;
use Planning;
use Planning_ArtifactMilestone;
use Planning_MilestoneFactory;
use PlanningFactory;
use PlanningPermissionsManager;
use Project;
use Psr\Log\NullLogger;
use Tracker;
use Tracker_ArtifactFactory;
use Tracker_FormElementFactory;
use Tuleap\AgileDashboard\Milestone\Criterion\Status\StatusAll;
use Tuleap\AgileDashboard\Milestone\Criterion\Status\StatusOpen;
use Tuleap\AgileDashboard\Milestone\MilestoneDao;
use Tuleap\AgileDashboard\Milestone\PaginatedMilestones;
use Tuleap\AgileDashboard\Milestone\Request\FilteringQuery;
use Tuleap\AgileDashboard\Milestone\Request\SiblingMilestoneRequest;
use Tuleap\AgileDashboard\Milestone\Request\SubMilestoneRequest;
use Tuleap\AgileDashboard\Milestone\Request\TopMilestoneRequest;
use Tuleap\AgileDashboard\Test\Builders\PlanningBuilder;
use Tuleap\Date\DatePeriodWithOpenDays;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\Test\Builders\ArtifactTestBuilder;
use Tuleap\Tracker\Test\Builders\ChangesetTestBuilder;
use Tuleap\Tracker\Test\Builders\Fields\DateFieldBuilder;
use Tuleap\Tracker\Test\Builders\Fields\IntFieldBuilder;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;
use Tuleap\Tracker\Test\Stub\Semantic\Timeframe\BuildSemanticTimeframeStub;
use Tuleap\Tracker\Test\Stub\Semantic\Timeframe\IComputeTimeframesStub;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class MilestoneFactoryGetPaginatedMilestonesTest extends TestCase
{
    private const PROJECT_ID = 101;

    private Planning_MilestoneFactory $milestone_factory;
    private PlanningFactory&MockObject $planning_factory;
    private MilestoneDao&MockObject $milestone_dao;
    private Tracker_ArtifactFactory&MockObject $artifact_factory;
    private Planning $top_planning;
    private TopMilestoneRequest $top_milestone_request;
    private SubMilestoneRequest $sub_milestone_request;
    private SiblingMilestoneRequest $sibling_milestone_request;
    private Tracker $sub_milestone_tracker;
    private Planning_ArtifactMilestone $reference_milestone;
    private Project $project;
    private Planning $sub_planning;
    private PFUser $user;

    protected function setUp(): void
    {
        $this->planning_factory = $this->createMock(PlanningFactory::class);
        $this->artifact_factory = $this->createMock(Tracker_ArtifactFactory::class);
        $this->milestone_dao    = $this->createMock(MilestoneDao::class);

        $form_element_factory = $this->createMock(Tracker_FormElementFactory::class);
        $form_element_factory->method('getComputableFieldByNameForUser');
        $this->milestone_factory = new Planning_MilestoneFactory(
            $this->planning_factory,
            $this->artifact_factory,
            $form_element_factory,
            $this->createMock(AgileDashboard_Milestone_MilestoneStatusCounter::class),
            $this->createMock(PlanningPermissionsManager::class),
            $this->milestone_dao,
            BuildSemanticTimeframeStub::withTimeframeCalculator(
                TrackerTestBuilder::aTracker()->build(),
                IComputeTimeframesStub::fromStartAndDuration(
                    DatePeriodWithOpenDays::buildFromDuration(1, 1),
                    DateFieldBuilder::aDateField(1)->build(),
                    IntFieldBuilder::anIntField(2)->build(),
                )
            ),
            new NullLogger(),
        );

        $this->user         = UserTestBuilder::aUser()->build();
        $this->project      = ProjectTestBuilder::aProject()->build();
        $this->top_planning = PlanningBuilder::aPlanning(self::PROJECT_ID)->withName('Release Planning')->build();

        $this->top_milestone_request = new TopMilestoneRequest(
            $this->user,
            $this->project,
            50,
            0,
            'asc',
            FilteringQuery::fromStatusQuery(new StatusAll())
        );

        $parent_milestone = new Planning_ArtifactMilestone(
            $this->project,
            $this->top_planning,
            ArtifactTestBuilder::anArtifact(121)->build(),
        );

        $this->sub_milestone_request = new SubMilestoneRequest(
            $this->user,
            $parent_milestone,
            50,
            0,
            'asc',
            new StatusAll()
        );

        $this->sub_milestone_tracker  = TrackerTestBuilder::aTracker()->withId(17)->build();
        $reference_milestone_artifact = ArtifactTestBuilder::anArtifact(121)
            ->inTracker($this->sub_milestone_tracker)
            ->build();

        $this->sub_planning        = PlanningBuilder::aPlanning(self::PROJECT_ID)->withName('Sprint Planning')->build();
        $this->reference_milestone = new Planning_ArtifactMilestone(
            $this->project,
            $this->sub_planning,
            $reference_milestone_artifact,
        );

        $this->sibling_milestone_request = new SiblingMilestoneRequest(
            $this->user,
            $this->reference_milestone,
            50,
            0,
            new StatusOpen()
        );
    }

    private function getTopMilestones(): PaginatedMilestones
    {
        $this->planning_factory->method('getVirtualTopPlanning')->willReturn($this->top_planning);

        return $this->milestone_factory->getPaginatedTopMilestones($this->top_milestone_request);
    }

    public function testItReturnsEmptyWhenNoMilestones(): void
    {
        $this->top_planning = PlanningBuilder::aPlanning(self::PROJECT_ID)
            ->withBadConfigurationAndNoMilestoneTracker()
            ->build();

        $milestones = $this->getTopMilestones();

        self::assertSame(0, $milestones->getTotalSize());
        self::assertEmpty($milestones->getMilestones());
    }

    public function testItReturnsMilestonesFilteredByStatus(): void
    {
        $milestone_tracker  = TrackerTestBuilder::aTracker()->withId(15)->build();
        $this->top_planning = PlanningBuilder::aPlanning(self::PROJECT_ID)
            ->withMilestoneTracker($milestone_tracker)
            ->build();
        $this->milestone_dao->expects($this->once())->method('searchPaginatedTopMilestones')
            ->with(15, $this->top_milestone_request)
            ->willReturn([['id' => 24], ['id' => 25]]);
        $this->milestone_dao->expects($this->once())->method('foundRows')->willReturn(2);

        $first_artifact  = $this->anArtifact(24, $milestone_tracker);
        $second_artifact = $this->anArtifact(25, $milestone_tracker);

        $this->artifact_factory->method('getInstanceFromRow')->willReturnOnConsecutiveCalls($first_artifact, $second_artifact);
        $this->planning_factory->method('getPlanningByPlanningTracker')->willReturn($this->top_planning);

        $milestones = $this->getTopMilestones();

        self::assertSame(2, $milestones->getTotalSize());
        $first_milestone = $milestones->getMilestones()[0];
        self::assertSame(24, $first_milestone->getArtifactId());
        $second_milestone = $milestones->getMilestones()[1];
        self::assertSame(25, $second_milestone->getArtifactId());
    }

    private function getSubMilestones(): PaginatedMilestones
    {
        $this->milestone_dao->method('searchPaginatedSubMilestones')->willReturn([]);
        $this->milestone_dao->method('foundRows')->willReturn(0);

        return $this->milestone_factory->getPaginatedSubMilestones($this->sub_milestone_request);
    }

    public function testItReturnsEmptyWhenNoSubMilestones(): void
    {
        $sub_milestones = $this->getSubMilestones();

        self::assertSame(0, $sub_milestones->getTotalSize());
        self::assertEmpty($sub_milestones->getMilestones());
    }

    public function testItReturnsSubMilestonesFilteredByStatus(): void
    {
        $this->milestone_dao->expects($this->once())->method('searchPaginatedSubMilestones')
            ->with(121, $this->sub_milestone_request)
            ->willReturn([['id' => 138], ['id' => 139]]);
        $this->milestone_dao->expects($this->once())->method('foundRows')->willReturn(2);

        $first_artifact  = $this->anArtifact(138, $this->sub_milestone_tracker);
        $second_artifact = $this->anArtifact(139, $this->sub_milestone_tracker);

        $this->artifact_factory->method('getInstanceFromRow')->willReturnOnConsecutiveCalls($first_artifact, $second_artifact);
        $this->planning_factory->method('getPlanningByPlanningTracker')
            ->with($this->user, $this->sub_milestone_tracker)
            ->willReturn($this->sub_planning);

        $sub_milestones = $this->getSubMilestones();

        self::assertSame(2, $sub_milestones->getTotalSize());
        $first_milestone = $sub_milestones->getMilestones()[0];
        self::assertSame(138, $first_milestone->getArtifactId());
        $second_milestone = $sub_milestones->getMilestones()[1];
        self::assertSame(139, $second_milestone->getArtifactId());
    }

    private function getSiblingMilestones(): PaginatedMilestones
    {
        return $this->milestone_factory->getPaginatedSiblingMilestones($this->sibling_milestone_request);
    }

    public function testItReturnsEmptyWhenNoSiblingTopMilestones(): void
    {
        $this->milestone_dao->method('searchPaginatedSiblingTopMilestones')->willReturn([]);
        $this->milestone_dao->method('foundRows')->willReturn(0);

        $sibling_milestones = $this->getSiblingMilestones();

        self::assertSame(0, $sibling_milestones->getTotalSize());
        self::assertEmpty($sibling_milestones->getMilestones());
    }

    public function testItReturnsSiblingTopMilestonesFilteredByStatus(): void
    {
        $top_milestone_tracker_id        = 69;
        $top_milestone_tracker           = TrackerTestBuilder::aTracker()->withId($top_milestone_tracker_id)->build();
        $reference_milestone_artifact    = ArtifactTestBuilder::anArtifact(93)
            ->inTracker($top_milestone_tracker)
            ->build();
        $this->reference_milestone       = new Planning_ArtifactMilestone(
            $this->project,
            $this->top_planning,
            $reference_milestone_artifact,
        );
        $this->sibling_milestone_request = new SiblingMilestoneRequest(
            $this->user,
            $this->reference_milestone,
            50,
            0,
            new StatusOpen()
        );

        $this->milestone_dao->expects($this->once())->method('searchPaginatedSiblingTopMilestones')
            ->with(93, $top_milestone_tracker_id, $this->sibling_milestone_request)
            ->willReturn([['id' => 138], ['id' => 139]]);
        $this->milestone_dao->expects($this->once())->method('foundRows')->willReturn(2);

        $first_artifact  = $this->anArtifact(138, $top_milestone_tracker);
        $second_artifact = $this->anArtifact(139, $top_milestone_tracker);

        $this->artifact_factory->method('getInstanceFromRow')->willReturn($first_artifact, $second_artifact);
        $this->planning_factory->method('getPlanningByPlanningTracker')
            ->with($this->user, $top_milestone_tracker)
            ->willReturn($this->top_planning);

        $sibling_milestones = $this->getSiblingMilestones();

        self::assertSame(2, $sibling_milestones->getTotalSize());
        $first_milestone = $sibling_milestones->getMilestones()[0];
        self::assertSame(138, $first_milestone->getArtifactId());
        $second_milestone = $sibling_milestones->getMilestones()[1];
        self::assertSame(139, $second_milestone->getArtifactId());
    }

    public function testItReturnsEmptyWhenNoSiblingSubMilestones(): void
    {
        $parent_milestone = new Planning_ArtifactMilestone(
            $this->project,
            $this->top_planning,
            ArtifactTestBuilder::anArtifact(462)->build(),
        );
        $this->reference_milestone->setAncestors([$parent_milestone]);

        $this->milestone_dao->method('searchPaginatedSiblingMilestones')->willReturn([]);
        $this->milestone_dao->method('foundRows')->willReturn(0);

        $sibling_milestones = $this->getSiblingMilestones();

        self::assertSame(0, $sibling_milestones->getTotalSize());
        self::assertEmpty($sibling_milestones->getMilestones());
    }

    public function testItReturnsSiblingSubMilestonesFilteredByStatus(): void
    {
        $parent_milestone = new Planning_ArtifactMilestone(
            $this->project,
            $this->top_planning,
            ArtifactTestBuilder::anArtifact(462)->build(),
        );
        $this->reference_milestone->setAncestors([$parent_milestone]);

        $this->milestone_dao->expects($this->once())->method('searchPaginatedSiblingMilestones')
            ->with(121, $this->sibling_milestone_request)
            ->willReturn([['id' => 138], ['id' => 139]]);
        $this->milestone_dao->expects($this->once())->method('foundRows')->willReturn(2);

        $first_artifact  = $this->anArtifact(138, $this->sub_milestone_tracker);
        $second_artifact = $this->anArtifact(139, $this->sub_milestone_tracker);

        $this->artifact_factory->method('getInstanceFromRow')->willReturnOnConsecutiveCalls($first_artifact, $second_artifact);
        $this->planning_factory->method('getPlanningByPlanningTracker')
            ->with($this->user, $this->sub_milestone_tracker)
            ->willReturn($this->sub_planning);

        $sibling_milestones = $this->getSiblingMilestones();

        self::assertSame(2, $sibling_milestones->getTotalSize());
        $first_milestone = $sibling_milestones->getMilestones()[0];
        self::assertSame(138, $first_milestone->getArtifactId());
        $second_milestone = $sibling_milestones->getMilestones()[1];
        self::assertSame(139, $second_milestone->getArtifactId());
    }

    private function anArtifact(int $artifact_id, Tracker $milestone_tracker): Artifact
    {
        $changeset = ChangesetTestBuilder::aChangeset(1)->build();

        return ArtifactTestBuilder::anArtifact($artifact_id)
            ->withTitle('title')
            ->inTracker($milestone_tracker)
            ->withChangesets($changeset)
            ->userCanView($this->user)
            ->withParent(null)
            ->isOpen(true)
            ->withAncestors([])
            ->build();
    }
}
