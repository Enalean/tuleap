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
use Project;
use ProjectManager;
use Tracker;
use TrackerFactory;

class PlannableItemsCollectionBuilderTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var PlannableItemsCollectionBuilder
     */
    private $builder;

    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|PlannableItemsTrackersDao
     */
    private $dao;

    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|TrackerFactory
     */
    private $tracker_factory;

    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|ProjectManager
     */
    private $project_manager;

    protected function setUp(): void
    {
        parent::setUp();

        $this->dao             = Mockery::mock(PlannableItemsTrackersDao::class);
        $this->tracker_factory = Mockery::mock(TrackerFactory::class);
        $this->project_manager = Mockery::mock(ProjectManager::class);

        $this->builder = new PlannableItemsCollectionBuilder(
            $this->dao,
            $this->tracker_factory,
            $this->project_manager
        );
    }

    public function testItBuildsAPlannableItemsCollection(): void
    {
        $program_top_planning = new Planning(1, 'Release Planning', 104, 'Release Backlog', 'Sprint Plan', []);

        $this->dao->shouldReceive('getPlannableItemsTrackerIds')
            ->once()
            ->with(1)
            ->andReturn([
                [
                    'project_id' => 144,
                    'tracker_ids' => "202, 210"
                ],
                [
                    'project_id' => 145,
                    'tracker_ids' => "245, 402"
                ],
            ]);

        $team_project_01 = Mockery::mock(Project::class);
        $team_project_02 = Mockery::mock(Project::class);

        $this->mockTeampProjectsManager(
            $team_project_01,
            $team_project_02
        );

        $team_project_01_tracker_01 = Mockery::mock(Tracker::class);
        $team_project_01_tracker_02 = Mockery::mock(Tracker::class);
        $team_project_02_tracker_01 = Mockery::mock(Tracker::class);
        $team_project_02_tracker_02 = Mockery::mock(Tracker::class);

        $this->mockTeampTrackersFactory(
            $team_project_01_tracker_01,
            $team_project_01_tracker_02,
            $team_project_02_tracker_01,
            $team_project_02_tracker_02
        );

        $collection = $this->builder->buildCollection($program_top_planning);

        $this->assertCount(2, $collection->getPlannableItems());

        $first_plannable_item = $collection->getPlannableItems()[0];
        $this->assertSame($team_project_01, $first_plannable_item->getProject());
        $this->assertCount(2, $first_plannable_item->getTrackers());
        $this->assertContains($team_project_01_tracker_01, $first_plannable_item->getTrackers());
        $this->assertContains($team_project_01_tracker_02, $first_plannable_item->getTrackers());

        $second_plannable_item = $collection->getPlannableItems()[1];
        $this->assertSame($team_project_02, $second_plannable_item->getProject());
        $this->assertCount(2, $second_plannable_item->getTrackers());
        $this->assertContains($team_project_02_tracker_01, $second_plannable_item->getTrackers());
        $this->assertContains($team_project_02_tracker_02, $second_plannable_item->getTrackers());
    }

    private function mockTeampTrackersFactory(
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

    private function mockTeampProjectsManager(
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
