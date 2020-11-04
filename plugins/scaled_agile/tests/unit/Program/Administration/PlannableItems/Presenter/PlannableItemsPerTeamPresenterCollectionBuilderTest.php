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

namespace Tuleap\ScaledAgile\Program\Administration\PlannableItems\Presenter;

use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PFUser;
use PHPUnit\Framework\TestCase;
use Planning;
use PlanningFactory;
use Project;
use Tuleap\ScaledAgile\Program\Administration\PlannableItems\PlannableItems;
use Tuleap\ScaledAgile\Program\Administration\PlannableItems\PlannableItemsCollection;
use Tuleap\ScaledAgile\Adapter\Program\PlanningAdapter;
use Tuleap\ScaledAgile\Adapter\ProjectDataAdapter;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;
use Tuleap\Tracker\TrackerColor;

final class PlannableItemsPerTeamPresenterCollectionBuilderTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var Project
     */
    private $team_project_02;

    /**
     * @var Project
     */
    private $team_project;

    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|PlanningFactory
     */
    private $planning_factory;

    /**
     * @var PlannableItemsPerTeamPresenterCollectionBuilder
     */
    private $builder;

    /**
     * @var PFUser
     */
    private $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->planning_factory = Mockery::mock(PlanningFactory::class);
        $planning_adapter       = new PlanningAdapter($this->planning_factory);

        $this->builder = new PlannableItemsPerTeamPresenterCollectionBuilder(
            $planning_adapter
        );

        $this->user = UserTestBuilder::aUser()->build();
        $this->team_project = new Project(['group_id' => 123, 'group_name' => 'Team 1', 'unix_group_name' => 'team_1']);
        $this->team_project_02 = new Project(['group_id' => 124, 'group_name' => 'Team 2', 'unix_group_name' => 'team_2']);
    }

    public function testItBuildsACollectionOfPresenterFromCollectionObject(): void
    {
        $plannable_items_collection = $this->buildPlannableItemsCollection();

        $this->getRootPlannings();

        $presenters_collection = $this->builder->buildPresenterCollectionFromObjectCollection(
            $this->user,
            $plannable_items_collection
        );

        $presenters = $presenters_collection->getPlannableItemsPerTeamPresenters();

        $this->assertPresenters($presenters);
    }

    /**
     * @param PlannableItemsPerTeamPresenter[] $presenters
     */
    private function assertPresenters(array $presenters): void
    {
        $this->assertCount(2, $presenters);

        $first_presenter = $presenters[0];
        $this->assertSame("Team 1", $first_presenter->project_name);
        $this->assertSame("bugs", $first_presenter->plannable_item_presenters[0]->tracker_name);
        $this->assertSame("user stories", $first_presenter->plannable_item_presenters[1]->tracker_name);
        $this->assertNotNull($first_presenter->configuration_link);

        $second_presenter = $presenters[1];
        $this->assertSame("Team 2", $second_presenter->project_name);
        $this->assertSame("bugs", $second_presenter->plannable_item_presenters[0]->tracker_name);
        $this->assertSame("stories", $second_presenter->plannable_item_presenters[1]->tracker_name);
        $this->assertNotNull($second_presenter->configuration_link);
    }

    private function buildPlannableItemsCollection(): PlannableItemsCollection
    {
        $silver               = TrackerColor::fromName('chrome-silver');
        $green                = TrackerColor::fromName('neon-green');

        $plannable_tracker_01 = TrackerTestBuilder::aTracker()->withId(1)->withName('bugs')->withColor($silver)->build();
        $plannable_tracker_02 = TrackerTestBuilder::aTracker()->withId(2)->withName('user stories')->withColor($green)->build();
        $plannable_tracker_03 = TrackerTestBuilder::aTracker()->withId(3)->withName('bugs')->withColor($silver)->build();
        $plannable_tracker_04 = TrackerTestBuilder::aTracker()->withId(4)->withName('stories')->withColor($green)->build();

        return new PlannableItemsCollection([
            new PlannableItems(
                ProjectDataAdapter::build($this->team_project),
                [
                    $plannable_tracker_01,
                    $plannable_tracker_02
                ]
            ),
            new PlannableItems(
                ProjectDataAdapter::build($this->team_project_02),
                [
                    $plannable_tracker_03,
                    $plannable_tracker_04
                ]
            )
        ]);
    }

    private function getRootPlannings(): void
    {
        $root_tracker   = TrackerTestBuilder::aTracker()->withId(1)->withProject($this->team_project)->build();
        $second_tracker = TrackerTestBuilder::aTracker()->withId(2)->withProject($this->team_project_02)->build();

        $first_root_planning = new Planning(43, 'Release Planning', $this->team_project->getID(), '', [302, 504]);
        $first_root_planning->setPlanningTracker($root_tracker);
        $second_root_planning = new Planning(49, 'Release Planning', $this->team_project_02->getID(), '', [302, 504]);
        $second_root_planning->setPlanningTracker($second_tracker);

        $this->planning_factory->shouldReceive('getRootPlanning')->twice()->andReturn(
            $first_root_planning,
            $second_root_planning
        );
    }
}
