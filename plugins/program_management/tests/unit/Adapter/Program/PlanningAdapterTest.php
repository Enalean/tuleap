<?php
/**
 * Copyright (c) Enalean, 2020 - Present. All Rights Reserved.
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

namespace Tuleap\ProgramManagement\Adapter\Program;

use Tuleap\AgileDashboard\Test\Builders\PlanningBuilder;
use Tuleap\ProgramManagement\Domain\Program\Admin\Configuration\ConfigurationErrorsCollector;
use Tuleap\ProgramManagement\Domain\Program\PlanningConfiguration\TopPlanningNotFoundInProjectException;
use Tuleap\ProgramManagement\Domain\Team\MirroredTimebox\PlanningHasNoMilestoneTrackerException;
use Tuleap\ProgramManagement\Tests\Stub\ProjectReferenceStub;
use Tuleap\ProgramManagement\Tests\Stub\RetrieveUserStub;
use Tuleap\ProgramManagement\Tests\Stub\UserIdentifierStub;
use Tuleap\ProgramManagement\Tests\Stub\VerifyIsTeamStub;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;

final class PlanningAdapterTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private const PROJECT_ID           = 101;
    private const MILESTONE_TRACKER_ID = 40;
    private PlanningAdapter $adapter;
    /**
     * @var \PHPUnit\Framework\MockObject\Stub&\PlanningFactory
     */
    private $planning_factory;
    private UserIdentifierStub $user_identifier;
    private ConfigurationErrorsCollector $error_collector;

    protected function setUp(): void
    {
        $this->planning_factory = $this->createStub(\PlanningFactory::class);
        $this->adapter          = new PlanningAdapter($this->planning_factory, RetrieveUserStub::withGenericUser());
        $this->user_identifier  = UserIdentifierStub::buildGenericUser();
        $this->error_collector  = new ConfigurationErrorsCollector(VerifyIsTeamStub::withValidTeam(), false);
    }

    public function testThrowExceptionIfRootPlanningDoesNotExist(): void
    {
        $project_id = self::PROJECT_ID;
        $this->planning_factory->method('getRootPlanning')->willReturn(false);

        $this->expectException(TopPlanningNotFoundInProjectException::class);
        $this->adapter->getRootPlanning($this->user_identifier, $project_id);
    }

    public function testThrowExceptionIfRootPlanningHasNoPlanningTracker(): void
    {
        $planning = PlanningBuilder::aPlanning(self::PROJECT_ID)
            ->withBadConfigurationAndNoMilestoneTracker()
            ->build();
        $this->planning_factory->method('getRootPlanning')->willReturn($planning);

        $this->expectException(PlanningHasNoMilestoneTrackerException::class);
        $this->adapter->getRootPlanning($this->user_identifier, self::PROJECT_ID);
    }

    public function testItBuildARootPlanning(): void
    {
        $project     = ProjectTestBuilder::aProject()->withId(self::PROJECT_ID)->build();
        $tracker     = TrackerTestBuilder::aTracker()->withId(self::MILESTONE_TRACKER_ID)->withProject($project)->build(
        );
        $planning_id = 170;
        $planning    = PlanningBuilder::aPlanning(self::PROJECT_ID)
            ->withId($planning_id)
            ->withMilestoneTracker($tracker)
            ->build();

        $this->planning_factory->method('getRootPlanning')->willReturn($planning);

        $team_planning = $this->adapter->getRootPlanning($this->user_identifier, self::PROJECT_ID);
        self::assertSame($planning_id, $team_planning->getId());
    }

    public function testItRetrievesTheRootMilestoneTracker(): void
    {
        $project  = ProjectTestBuilder::aProject()->withId(self::PROJECT_ID)->build();
        $tracker  = TrackerTestBuilder::aTracker()->withId(self::MILESTONE_TRACKER_ID)->withProject($project)->build();
        $planning = PlanningBuilder::aPlanning(self::PROJECT_ID)
            ->withMilestoneTracker($tracker)
            ->build();

        $this->planning_factory->method('getRootPlanning')->willReturn($planning);

        $wrapper_project = ProjectReferenceStub::withValues(self::PROJECT_ID, 'Team Blue', 'team_blue', '');
        self::assertSame(
            self::MILESTONE_TRACKER_ID,
            $this->adapter->retrieveRootPlanningMilestoneTracker(
                $wrapper_project,
                UserIdentifierStub::buildGenericUser(),
                $this->error_collector
            )?->getId()
        );
    }

    public function testItCollectErrorWhenNoPlanning(): void
    {
        $this->planning_factory->method('getRootPlanning')->willReturn(false);

        $wrapper_project = ProjectReferenceStub::withValues(self::PROJECT_ID, 'Team Blue', 'team_blue', '');

        $this->adapter->retrieveRootPlanningMilestoneTracker(
            $wrapper_project,
            UserIdentifierStub::buildGenericUser(),
            $this->error_collector
        );

        self::assertCount(1, $this->error_collector->getNoMilestonePlanning());
    }

    public function testItCollectErrorWhenNoRootPlanning(): void
    {
        $this->planning_factory->method('getRootPlanning')->willReturn(false);

        $wrapper_project = ProjectReferenceStub::buildGeneric();
        $this->adapter->retrieveSecondPlanningMilestoneTracker(
            $wrapper_project,
            UserIdentifierStub::buildGenericUser(),
            $this->error_collector
        );

        self::assertCount(1, $this->error_collector->getNoMilestonePlanning());
    }

    public function testItTCollectsErrorIfNoSecondPlanningMilestoneInProject(): void
    {
        $project       = ProjectTestBuilder::aProject()->withId(self::PROJECT_ID)->build();
        $root_tracker  = TrackerTestBuilder::aTracker()->withId(self::MILESTONE_TRACKER_ID)->withProject(
            $project
        )->build();
        $root_planning = PlanningBuilder::aPlanning(self::PROJECT_ID)
            ->withMilestoneTracker($root_tracker)
            ->build();

        $this->planning_factory->method('getRootPlanning')->willReturn($root_planning);
        $this->planning_factory->method('getChildrenPlanning')->willReturn(null);

        $wrapper_project = ProjectReferenceStub::withValues(self::PROJECT_ID, 'Team Blue', 'team_blue', '');
        $this->adapter->retrieveSecondPlanningMilestoneTracker(
            $wrapper_project,
            UserIdentifierStub::buildGenericUser(),
            $this->error_collector
        );

        self::assertCount(1, $this->error_collector->getNoSprintPlanning());
    }

    public function testItThrowErrorIfNoTrackerInSecondPlanningMilestoneInProject(): void
    {
        $project       = ProjectTestBuilder::aProject()->withId(self::PROJECT_ID)->build();
        $root_tracker  = TrackerTestBuilder::aTracker()->withId(self::MILESTONE_TRACKER_ID)->withProject(
            $project
        )->build();
        $root_planning = PlanningBuilder::aPlanning(self::PROJECT_ID)
            ->withMilestoneTracker($root_tracker)
            ->build();

        $second_planning = PlanningBuilder::aPlanning(self::PROJECT_ID)
            ->withBadConfigurationAndNoMilestoneTracker()
            ->build();

        $this->planning_factory->method('getRootPlanning')->willReturn($root_planning);
        $this->planning_factory->method('getChildrenPlanning')->willReturn($second_planning);

        $wrapper_project = ProjectReferenceStub::withValues(self::PROJECT_ID, 'Team Blue', 'team_blue', '');
        $this->expectException(PlanningHasNoMilestoneTrackerException::class);
        $this->adapter->retrieveSecondPlanningMilestoneTracker(
            $wrapper_project,
            UserIdentifierStub::buildGenericUser(),
            $this->error_collector
        );
    }

    public function testItReturnSecondPlanningMilestoneTracker(): void
    {
        $project       = ProjectTestBuilder::aProject()->withId(self::PROJECT_ID)->build();
        $root_planning = PlanningBuilder::aPlanning(self::PROJECT_ID)->build();

        $second_milestone_tracker_id = 85;
        $second_tracker              = TrackerTestBuilder::aTracker()->withId(
            $second_milestone_tracker_id
        )->withProject($project)->build();
        $second_planning             = PlanningBuilder::aPlanning(self::PROJECT_ID)
            ->withMilestoneTracker($second_tracker)
            ->build();
        $this->planning_factory->method('getRootPlanning')->willReturn($root_planning);
        $this->planning_factory->method('getChildrenPlanning')->willReturn($second_planning);

        $wrapper_project = ProjectReferenceStub::withValues(self::PROJECT_ID, 'Team Blue', 'team_blue', '');
        self::assertSame(
            $second_milestone_tracker_id,
            $this->adapter->retrieveSecondPlanningMilestoneTracker(
                $wrapper_project,
                UserIdentifierStub::buildGenericUser(),
                $this->error_collector
            )?->getId()
        );
    }
}
