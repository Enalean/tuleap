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

namespace Tuleap\MultiProjectBacklog\Aggregator\PlannableItems;

use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
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
        $aggregator_project = Mockery::mock(Project::class);
        $aggregator_project->shouldReceive('getID')->andReturn(143);

        $this->dao->shouldReceive('getPlannableItemsTrackerIds')
            ->once()
            ->with(143)
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

        $contributor_project_01 = Mockery::mock(Project::class);
        $contributor_project_02 = Mockery::mock(Project::class);

        $this->mockContributorProjectsManager(
            $contributor_project_01,
            $contributor_project_02
        );

        $contributor_project_01_tracker_01 = Mockery::mock(Tracker::class);
        $contributor_project_01_tracker_02 = Mockery::mock(Tracker::class);
        $contributor_project_02_tracker_01 = Mockery::mock(Tracker::class);
        $contributor_project_02_tracker_02 = Mockery::mock(Tracker::class);

        $this->mockContributorTrackersFactory(
            $contributor_project_01_tracker_01,
            $contributor_project_01_tracker_02,
            $contributor_project_02_tracker_01,
            $contributor_project_02_tracker_02
        );

        $collection = $this->builder->buildCollection($aggregator_project);

        $this->assertCount(2, $collection->getPlannableItems());

        $first_plannable_item = $collection->getPlannableItems()[0];
        $this->assertSame($contributor_project_01, $first_plannable_item->getProject());
        $this->assertCount(2, $first_plannable_item->getTrackers());
        $this->assertContains($contributor_project_01_tracker_01, $first_plannable_item->getTrackers());
        $this->assertContains($contributor_project_01_tracker_02, $first_plannable_item->getTrackers());

        $second_plannable_item = $collection->getPlannableItems()[1];
        $this->assertSame($contributor_project_02, $second_plannable_item->getProject());
        $this->assertCount(2, $second_plannable_item->getTrackers());
        $this->assertContains($contributor_project_02_tracker_01, $second_plannable_item->getTrackers());
        $this->assertContains($contributor_project_02_tracker_02, $second_plannable_item->getTrackers());
    }

    private function mockContributorTrackersFactory(
        Tracker $contributor_project_01_tracker_01,
        Tracker $contributor_project_01_tracker_02,
        Tracker $contributor_project_02_tracker_01,
        Tracker $contributor_project_02_tracker_02
    ): void {
        $this->tracker_factory->shouldReceive('getTrackerById')
            ->once()
            ->with(202)
            ->andReturn($contributor_project_01_tracker_01);

        $this->tracker_factory->shouldReceive('getTrackerById')
            ->once()
            ->with(210)
            ->andReturn($contributor_project_01_tracker_02);

        $this->tracker_factory->shouldReceive('getTrackerById')
            ->once()
            ->with(245)
            ->andReturn($contributor_project_02_tracker_01);

        $this->tracker_factory->shouldReceive('getTrackerById')
            ->once()
            ->with(402)
            ->andReturn($contributor_project_02_tracker_02);
    }

    private function mockContributorProjectsManager(
        Project $contributor_project_01,
        Project $contributor_project_02
    ): void {
        $this->project_manager->shouldReceive('getProject')
            ->with(144)
            ->once()
            ->andReturn($contributor_project_01);

        $this->project_manager->shouldReceive('getProject')
            ->with(145)
            ->once()
            ->andReturn($contributor_project_02);
    }
}
