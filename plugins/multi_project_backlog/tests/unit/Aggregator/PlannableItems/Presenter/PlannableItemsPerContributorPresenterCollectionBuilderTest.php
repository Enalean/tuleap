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

namespace Tuleap\MultiProjectBacklog\Aggregator\PlannableItems\Presenter;

use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PFUser;
use PHPUnit\Framework\TestCase;
use Planning;
use PlanningFactory;
use Project;
use Tracker;
use Tuleap\MultiProjectBacklog\Aggregator\PlannableItems\PlannableItems;
use Tuleap\MultiProjectBacklog\Aggregator\PlannableItems\PlannableItemsCollection;
use Tuleap\Tracker\TrackerColor;

class PlannableItemsPerContributorPresenterCollectionBuilderTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var PlannableItemsPerContributorPresenterCollectionBuilder
     */
    private $builder;

    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|PlanningFactory
     */
    private $planning_factory;

    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|PFUser
     */
    private $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->planning_factory = Mockery::mock(PlanningFactory::class);

        $this->builder = new PlannableItemsPerContributorPresenterCollectionBuilder(
            $this->planning_factory
        );

        $this->user = Mockery::mock(PFUser::class);
    }

    public function testItBuildsACollectionOfPresenterFromCollectionObject(): void
    {
        $plannable_items_collection = $this->buildPlannableItemsCollection();

        $this->mockRootPlannings();

        $presenters_collection = $this->builder->buildPresenterCollectionFromObjectCollection(
            $this->user,
            $plannable_items_collection
        );

        $presenters = $presenters_collection->getPlannableItemsPerContributorPresenters();

        $this->assertPresenters($presenters);
    }

    /**
     * @param PlannableItemsPerContributorPresenter[] $presenters
     */
    private function assertPresenters(array $presenters): void
    {
        $this->assertCount(2, $presenters);

        $first_presenter = $presenters[0];
        $this->assertSame("Contributor 1", $first_presenter->project_name);
        $this->assertSame("bugs", $first_presenter->plannable_item_presenters[0]->tracker_name);
        $this->assertSame("user stories", $first_presenter->plannable_item_presenters[1]->tracker_name);
        $this->assertNotNull($first_presenter->configuration_link);

        $second_presenter = $presenters[1];
        $this->assertSame("Contributor 2", $second_presenter->project_name);
        $this->assertSame("bugs", $second_presenter->plannable_item_presenters[0]->tracker_name);
        $this->assertSame("stories", $second_presenter->plannable_item_presenters[1]->tracker_name);
        $this->assertNotNull($second_presenter->configuration_link);
    }

    private function buildPlannableItemsCollection(): PlannableItemsCollection
    {
        $contributor_project  = Mockery::mock(Project::class);
        $contributor_project->shouldReceive('getID')->andReturn('123');
        $contributor_project->shouldReceive('getPublicName')->andReturn('Contributor 1');

        $plannable_tracker_01 = Mockery::mock(Tracker::class);
        $plannable_tracker_02 = Mockery::mock(Tracker::class);

        $plannable_tracker_01->shouldReceive('getName')->andReturn('bugs');
        $plannable_tracker_01->shouldReceive('getColor')->andReturn(
            TrackerColor::fromName('chrome-silver')
        );

        $plannable_tracker_02->shouldReceive('getName')->andReturn('user stories');
        $plannable_tracker_02->shouldReceive('getColor')->andReturn(
            TrackerColor::fromName('neon-green')
        );

        $contributor_project_02  = Mockery::mock(Project::class);
        $contributor_project_02->shouldReceive('getID')->andReturn('124');
        $contributor_project_02->shouldReceive('getPublicName')->andReturn('Contributor 2');

        $plannable_tracker_03 = Mockery::mock(Tracker::class);
        $plannable_tracker_04 = Mockery::mock(Tracker::class);

        $plannable_tracker_03->shouldReceive('getName')->andReturn('bugs');
        $plannable_tracker_03->shouldReceive('getColor')->andReturn(
            TrackerColor::fromName('chrome-silver')
        );

        $plannable_tracker_04->shouldReceive('getName')->andReturn('stories');
        $plannable_tracker_04->shouldReceive('getColor')->andReturn(
            TrackerColor::fromName('neon-green')
        );

        return new PlannableItemsCollection([
            new PlannableItems(
                $contributor_project,
                [
                    $plannable_tracker_01,
                    $plannable_tracker_02
                ]
            ),
            new PlannableItems(
                $contributor_project_02,
                [
                    $plannable_tracker_03,
                    $plannable_tracker_04
                ]
            )
        ]);
    }

    private function mockRootPlannings(): void
    {
        $first_root_planning = Mockery::mock(Planning::class);
        $first_root_planning->shouldReceive('getId')->andReturn(43);

        $second_root_planning = Mockery::mock(Planning::class);
        $second_root_planning->shouldReceive('getId')->andReturn(49);

        $this->planning_factory->shouldReceive('getRootPlanning')->twice()->andReturn(
            $first_root_planning,
            $second_root_planning
        );
    }
}
