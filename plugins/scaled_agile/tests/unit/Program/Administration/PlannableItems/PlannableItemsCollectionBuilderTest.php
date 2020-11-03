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
use Project;
use ProjectManager;
use Tracker;
use TrackerFactory;
use Tuleap\ScaledAgile\Program\PlanningConfiguration\PlanningData;
use Tuleap\ScaledAgile\ProjectData;
use Tuleap\ScaledAgile\Adapter\ProjectDataAdapter;
use Tuleap\ScaledAgile\TrackerData;
use Tuleap\ScaledAgile\Adapter\TrackerDataAdapter;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;

final class PlannableItemsCollectionBuilderTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|TrackerFactory
     */
    private $tracker_factory;

    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|ProjectManager
     */
    private $project_manager;

    /**
     * @var PlannableItemsCollectionBuilder
     */
    private $builder;

    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|PlannableItemsTrackersDao
     */
    private $dao;

    protected function setUp(): void
    {
        parent::setUp();

        $this->dao             = Mockery::mock(PlannableItemsTrackersDao::class);
        $this->tracker_factory = Mockery::mock(TrackerFactory::class);
        $tracker_data_adapter  = new TrackerDataAdapter($this->tracker_factory);
        $this->project_manager = Mockery::mock(ProjectManager::class);
        $project_data_adapter  = new ProjectDataAdapter($this->project_manager);

        $this->builder = new PlannableItemsCollectionBuilder(
            $this->dao,
            $tracker_data_adapter,
            $project_data_adapter
        );
    }

    public function testItBuildsAPlannableItemsCollection(): void
    {
        $program_tracker = TrackerTestBuilder::aTracker()
            ->withId(1)
            ->withProject(new Project(['group_id' => 105]))
            ->build();

        $program_top_planning = new PlanningData(
            new TrackerData($program_tracker),
            1,
            'Release Planning',
            [],
            new ProjectData(1, "my_project", "My project")
        );

        $this->dao->shouldReceive('getPlannableItemsTrackerIds')
            ->once()
            ->with(1)
            ->andReturn(
                [
                    [
                        'project_id'  => 144,
                        'tracker_ids' => "202, 210"
                    ],
                    [
                        'project_id'  => 145,
                        'tracker_ids' => "245, 402"
                    ],
                ]
            );

        $team_project_01 = new Project(
            ['group_id' => 101, 'unix_group_name' => 'plannable_a', 'group_name' => 'Plan A']
        );
        $team_project_02 = new Project(
            ['group_id' => 102, 'unix_group_name' => 'plannable_', 'group_name' => 'Plan A']
        );

        $this->mockTeamProjectsManager(
            $team_project_01,
            $team_project_02
        );

        $team_project_01_tracker_01 = TrackerTestBuilder::aTracker()->withId(1)->build();
        $team_project_01_tracker_02 = TrackerTestBuilder::aTracker()->withId(2)->build();
        $team_project_02_tracker_01 = TrackerTestBuilder::aTracker()->withId(3)->build();
        $team_project_02_tracker_02 = TrackerTestBuilder::aTracker()->withId(4)->build();

        $this->mockTeamTrackersFactory(
            $team_project_01_tracker_01,
            $team_project_01_tracker_02,
            $team_project_02_tracker_01,
            $team_project_02_tracker_02
        );

        $collection = $this->builder->buildCollection($program_top_planning);

        $this->assertCount(2, $collection->getPlannableItems());

        $first_plannable_item = $collection->getPlannableItems()[0];
        $this->assertEquals(ProjectDataAdapter::build($team_project_01), $first_plannable_item->getProjectData());
        $this->assertCount(2, $first_plannable_item->getTrackersData());
        $this->assertEquals(
            TrackerDataAdapter::build($team_project_01_tracker_01),
            $first_plannable_item->getTrackersData()[0]
        );
        $this->assertEquals(
            TrackerDataAdapter::build($team_project_01_tracker_02),
            $first_plannable_item->getTrackersData()[1]
        );

        $second_plannable_item = $collection->getPlannableItems()[1];
        $this->assertEquals(ProjectDataAdapter::build($team_project_02), $second_plannable_item->getProjectData());
        $this->assertCount(2, $second_plannable_item->getTrackersData());
        $this->assertEquals(
            TrackerDataAdapter::build($team_project_02_tracker_01),
            $second_plannable_item->getTrackersData()[0]
        );
        $this->assertEquals(
            TrackerDataAdapter::build($team_project_02_tracker_02),
            $second_plannable_item->getTrackersData()[1]
        );
    }

    private function mockTeamTrackersFactory(
        Tracker $team_project_01_tracker_01,
        Tracker $team_project_01_tracker_02,
        Tracker $team_project_02_tracker_01,
        Tracker $team_project_02_tracker_02
    ): void {
        $this->tracker_factory->shouldReceive('getTrackerById')
            ->once()
            ->with(202)
            ->andReturn($team_project_01_tracker_01);

        $this->tracker_factory->shouldReceive('getTrackerById')
            ->once()
            ->with(210)
            ->andReturn($team_project_01_tracker_02);

        $this->tracker_factory->shouldReceive('getTrackerById')
            ->once()
            ->with(245)
            ->andReturn($team_project_02_tracker_01);

        $this->tracker_factory->shouldReceive('getTrackerById')
            ->once()
            ->with(402)
            ->andReturn($team_project_02_tracker_02);
    }

    private function mockTeamProjectsManager(
        Project $team_project_01,
        Project $team_project_02
    ): void {
        $this->project_manager->shouldReceive('getProject')
            ->with(144)
            ->once()
            ->andReturn($team_project_01);

        $this->project_manager->shouldReceive('getProject')
            ->with(145)
            ->once()
            ->andReturn($team_project_02);
    }
}
