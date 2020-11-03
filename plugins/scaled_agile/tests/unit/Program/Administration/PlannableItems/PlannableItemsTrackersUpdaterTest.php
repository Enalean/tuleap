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

namespace Tuleap\ScaledAgile\Program\Administration\PlannableItems;

use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Planning;
use PlanningFactory;
use Project;
use Tuleap\ScaledAgile\Adapter\Program\PlanningAdapter;
use Tuleap\ScaledAgile\Program\PlanningConfiguration\PlanningData;
use Tuleap\ScaledAgile\ProjectData;
use Tuleap\ScaledAgile\Team\TeamDao;
use Tuleap\ScaledAgile\TrackerData;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\DB\DBTransactionExecutorPassthrough;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;

final class PlannableItemsTrackersUpdaterTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|PlanningFactory
     */
    private $planning_factory;

    /**
     * @var PlannableItemsTrackersUpdater
     */
    private $updater;

    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|TeamDao
     */
    private $team_dao;

    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|PlannableItemsTrackersDao
     */
    private $plannable_items_trackers_dao;

    protected function setUp(): void
    {
        parent::setUp();

        $this->team_dao                     = Mockery::mock(TeamDao::class);
        $this->plannable_items_trackers_dao = Mockery::mock(PlannableItemsTrackersDao::class);
        $this->planning_factory             = Mockery::mock(PlanningFactory::class);
        $planning_adapter                   = new PlanningAdapter($this->planning_factory);

        $this->updater = new PlannableItemsTrackersUpdater(
            $this->team_dao,
            $this->plannable_items_trackers_dao,
            new DBTransactionExecutorPassthrough(),
            $planning_adapter
        );
    }

    public function testItUpdatesThePlannableItemsTrackers(): void
    {
        $project         = new Project(['group_id' => 105, 'unix_group_name' => "project", "group_name" => "Project"]);
        $program_tracker = TrackerTestBuilder::aTracker()
            ->withId(1)
            ->withProject($project)
            ->build();

        $program_top_planning = new Planning(1, 'Release planning', $project->getId(), '', '');
        $program_top_planning->setPlanningTracker($program_tracker);

        $updated_planning_data     = new PlanningData(
            new TrackerData($program_tracker),
            3,
            'Release Planning',
            [302, 504],
            new ProjectData(105, "my_project", "My project")
        );

        $user = UserTestBuilder::aUser()->build();

        $this->team_dao->shouldReceive('isProjectATeamProject')
            ->with(105)
            ->once()
            ->andReturnTrue();

        $this->team_dao->shouldReceive('getProgramProjectsOfAGivenTeamProject')
            ->with(105)
            ->once()
            ->andReturn([['program_project_id' => 102]]);

        $this->plannable_items_trackers_dao->shouldReceive('deletePlannableItemsTrackerIdsOfAGivenTeamProject')
            ->once();

        $this->planning_factory->shouldReceive('getRootPlanning')
            ->once()
            ->with($user, 102)
            ->andReturn($program_top_planning);

        $this->plannable_items_trackers_dao->shouldReceive('addPlannableItemsTrackerIds')
            ->with(
                1,
                [302, 504]
            )
            ->once();

        $this->updater->updatePlannableItemsTrackersFromPlanning($updated_planning_data, $user);
    }

    public function testItDoesNothingIfThePlanningIsNotInATeamProject(): void
    {
        $program_tracker  = TrackerTestBuilder::aTracker()
            ->withId(1)
            ->withProject(new Project(['group_id' => 105]))
            ->build();
        $updated_planning = new PlanningData(
            new TrackerData($program_tracker),
            3,
            'Release Planning',
            [302, 504],
            new ProjectData(105, "my_project", "My project")
        );
        $user             = UserTestBuilder::aUser()->build();

        $this->team_dao->shouldReceive('isProjectATeamProject')
            ->with(105)
            ->once()
            ->andReturnFalse();

        $this->plannable_items_trackers_dao->shouldNotReceive('deletePlannableItemsTrackerIdsOfAGivenTeamProject');
        $this->team_dao->shouldNotReceive('getProgramProjectsOfAGivenTeamProject');
        $this->plannable_items_trackers_dao->shouldNotReceive('addPlannableItemsTrackerIds');

        $this->updater->updatePlannableItemsTrackersFromPlanning($updated_planning, $user);
    }
}
