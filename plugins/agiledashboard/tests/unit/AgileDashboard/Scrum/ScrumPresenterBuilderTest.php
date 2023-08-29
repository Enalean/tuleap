<?php
/**
 * Copyright (c) Enalean, 2019 - Present. All Rights Reserved.
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

namespace Tuleap\AgileDashboard\Scrum;

use AdminScrumPresenter;
use AgileDashboard_ConfigurationManager;
use EventManager;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Planning;
use Planning_PlanningAdminPresenter;
use PlanningFactory;
use Tuleap\AgileDashboard\Event\GetAdditionalScrumAdminSection;
use Tuleap\AgileDashboard\ExplicitBacklog\ExplicitBacklogDao;
use Tuleap\AgileDashboard\MonoMilestone\ScrumForMonoMilestoneChecker;
use Tuleap\AgileDashboard\Workflow\AddToTopBacklogPostActionDao;
use Tuleap\GlobalLanguageMock;
use Tuleap\Kanban\CheckSplitKanbanConfiguration;
use Tuleap\Test\Builders\ProjectTestBuilder;

class ScrumPresenterBuilderTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use MockeryPHPUnitIntegration;
    use GlobalLanguageMock;

    /**
     * @var ScrumPresenterBuilder
     */
    private $scrum_presenter_builder;
    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|ExplicitBacklogDao
     */
    private $explicit_backlog_dao;
    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|PlanningFactory
     */
    private $planning_factory;
    /**
     * @var EventManager|\Mockery\LegacyMockInterface|\Mockery\MockInterface
     */
    private $event_manager;
    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|ScrumForMonoMilestoneChecker
     */
    private $scrum_mono_milestone_checker;

    /**
     * @var AgileDashboard_ConfigurationManager|\Mockery\LegacyMockInterface|\Mockery\MockInterface
     */
    private $config_manager;

    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|AddToTopBacklogPostActionDao
     */
    private $add_to_top_backlog_post_action_dao;

    protected function setUp(): void
    {
        parent::setUp();

        $this->config_manager                     = Mockery::mock(AgileDashboard_ConfigurationManager::class);
        $this->scrum_mono_milestone_checker       = Mockery::mock(ScrumForMonoMilestoneChecker::class);
        $this->event_manager                      = Mockery::mock(EventManager::class);
        $this->planning_factory                   = Mockery::mock(PlanningFactory::class);
        $this->explicit_backlog_dao               = Mockery::mock(ExplicitBacklogDao::class);
        $this->add_to_top_backlog_post_action_dao = Mockery::mock(AddToTopBacklogPostActionDao::class);

        $this->scrum_presenter_builder = new ScrumPresenterBuilder(
            $this->config_manager,
            $this->scrum_mono_milestone_checker,
            $this->event_manager,
            $this->planning_factory,
            $this->explicit_backlog_dao,
            $this->add_to_top_backlog_post_action_dao,
            new CheckSplitKanbanConfiguration(),
        );

        $this->add_to_top_backlog_post_action_dao->shouldReceive('isAtLeastOnePostActionDefinedInProject')->andReturnTrue();
    }

    public function testItBuildsPresenterWhenNoRootPlanning(): void
    {
        $user = Mockery::mock(\PFUser::class);
        $user->shouldReceive('useLabFeatures')->andReturn('0');
        $project = ProjectTestBuilder::aProject()
            ->withId(101)
            ->withUsedService('plugin_agiledashboard')
            ->build();

        $root_planning = false;
        $this->planning_factory->shouldReceive('getRootPlanning')->atLeast(1)->andReturn($root_planning);

        $this->config_manager->shouldReceive('scrumIsActivatedForProject')->once()->andReturnFalse();
        $this->config_manager->shouldReceive('getScrumTitle')->once()->andReturn("Scrum");

        $planning = Mockery::mock(Planning::class);
        $planning->shouldReceive('getId')->andReturn(42);
        $this->planning_factory->shouldReceive('getPlanningsOutOfRootPlanningHierarchy')->once()->andReturn($planning);
        $this->planning_factory->shouldReceive('getPlannings')->once()->andReturn([$planning]);

        $this->scrum_mono_milestone_checker->shouldReceive('isScrumMonoMilestoneAvailable')->once()->andReturnFalse();
        $this->scrum_mono_milestone_checker->shouldReceive('isMonoMilestoneEnabled')->atLeast(1)->andReturnFalse();

        $this->event_manager->shouldReceive('processEvent')->once();
        $this->event_manager->shouldReceive('dispatch')->once();

        $this->explicit_backlog_dao->shouldReceive('isProjectUsingExplicitBacklog')->andReturnFalse();

        $expected_presenter = new AdminScrumPresenter(
            [new Planning_PlanningAdminPresenter(
                $planning,
                false
            ),
            ],
            101,
            true,
            "",
            [],
            false,
            "Scrum",
            false,
            false,
            true,
            "",
            false,
            true,
            [],
            false,
            false,
        );

        $additional_sections_event = new GetAdditionalScrumAdminSection(Mockery::mock(\Project::class));

        $presenter = $this->scrum_presenter_builder->getAdminScrumPresenter($user, $project, $additional_sections_event);

        $this->assertEquals($expected_presenter, $presenter);
    }

    public function testItBuildsPresenterInMonoMilestoneContextWhenMonoMilestoneAreDisabled(): void
    {
        $user = Mockery::mock(\PFUser::class);
        $user->shouldReceive('useLabFeatures')->andReturn('0');
        $project = ProjectTestBuilder::aProject()
            ->withId(101)
            ->withUsedService('plugin_agiledashboard')
            ->build();

        $planning = Mockery::mock(Planning::class);
        $planning->shouldReceive('getId')->andReturn(42);

        $tracker = Mockery::mock(\Tracker::class);
        $planning->shouldReceive('getPlanningTracker')->andReturn($tracker);
        $planning->shouldReceive('getName')->once()->andReturn("tracker name");
        $this->planning_factory->shouldReceive('getRootPlanning')->atLeast(1)->andReturn($planning);

        $this->config_manager->shouldReceive('scrumIsActivatedForProject')->once()->andReturnTrue();
        $this->config_manager->shouldReceive('getScrumTitle')->once()->andReturn("Scrum");

        $this->planning_factory->shouldReceive('getAvailablePlanningTrackers')->once()->andReturn([]);

        $this->planning_factory->shouldReceive('getPlanningsOutOfRootPlanningHierarchy')->once()->andReturn($planning);
        $this->planning_factory->shouldReceive('getPlannings')->once()->andReturn([$planning]);

        $this->event_manager->shouldReceive('processEvent')->once();
        $this->event_manager->shouldReceive('dispatch')->once();

        $this->scrum_mono_milestone_checker->shouldReceive('isScrumMonoMilestoneAvailable')->once()->andReturnTrue();
        $this->scrum_mono_milestone_checker->shouldReceive('isMonoMilestoneEnabled')->andReturnFalse();

        $this->explicit_backlog_dao->shouldReceive('isProjectUsingExplicitBacklog')->andReturnFalse();

        $this->planning_factory->shouldReceive('getPotentialPlanningTrackers')->once()->andReturn([]);

        $expected_presenter = new AdminScrumPresenter(
            [new Planning_PlanningAdminPresenter(
                $planning,
                false
            ),
            ],
            101,
            false,
            "tracker name",
            [],
            true,
            "Scrum",
            true,
            false,
            false,
            "",
            false,
            true,
            [],
            false,
            false,
        );

        $additional_sections_event = new GetAdditionalScrumAdminSection(Mockery::mock(\Project::class));

        $presenter = $this->scrum_presenter_builder->getAdminScrumPresenter($user, $project, $additional_sections_event);

        $this->assertEquals($expected_presenter, $presenter);
    }

    public function testItBuildsPresenterInMonoMilestoneContextWhenMonoMilestoneAreEnabled(): void
    {
        $user = Mockery::mock(\PFUser::class);
        $user->shouldReceive('useLabFeatures')->andReturn('0');
        $project = ProjectTestBuilder::aProject()
            ->withId(101)
            ->withUsedService('plugin_agiledashboard')
            ->build();

        $planning = Mockery::mock(Planning::class);
        $planning->shouldReceive('getId')->andReturn(42);

        $tracker = Mockery::mock(\Tracker::class);
        $planning->shouldReceive('getPlanningTracker')->andReturn($tracker);
        $planning->shouldReceive('getName')->once()->andReturn("tracker name");
        $this->planning_factory->shouldReceive('getRootPlanning')->atLeast(1)->andReturn($planning);

        $this->config_manager->shouldReceive('scrumIsActivatedForProject')->once()->andReturnTrue();
        $this->config_manager->shouldReceive('getScrumTitle')->once()->andReturn("Scrum");

        $this->planning_factory->shouldReceive('getAvailablePlanningTrackers')->once()->andReturn([]);
        $this->planning_factory->shouldReceive('getPlanningsOutOfRootPlanningHierarchy')->once()->andReturn($planning);
        $this->planning_factory->shouldReceive('getPlannings')->once()->andReturn([$planning]);

        $this->event_manager->shouldReceive('processEvent')->once();
        $this->event_manager->shouldReceive('dispatch')->once();

        $this->scrum_mono_milestone_checker->shouldReceive('isScrumMonoMilestoneAvailable')->once()->andReturnTrue();
        $this->scrum_mono_milestone_checker->shouldReceive('isMonoMilestoneEnabled')->andReturnTrue();
        $this->scrum_mono_milestone_checker->shouldReceive(
            'doesScrumMonoMilestoneConfigurationAllowsPlanningCreation'
        )->andReturnTrue();

        $this->explicit_backlog_dao->shouldReceive('isProjectUsingExplicitBacklog')->andReturnFalse();

        $this->planning_factory->shouldReceive('getPotentialPlanningTrackers')->once()->andReturn([]);

        $expected_presenter = new AdminScrumPresenter(
            [new Planning_PlanningAdminPresenter(
                $planning,
                false
            ),
            ],
            101,
            false,
            "tracker name",
            [],
            true,
            "Scrum",
            true,
            true,
            true,
            "",
            false,
            true,
            [],
            false,
            false,
        );

        $additional_sections_event = new GetAdditionalScrumAdminSection(Mockery::mock(\Project::class));

        $presenter = $this->scrum_presenter_builder->getAdminScrumPresenter($user, $project, $additional_sections_event);

        $this->assertEquals($expected_presenter, $presenter);
    }

    public function testItBuildsPresenterInExplicitBacklogContext(): void
    {
        $user = Mockery::mock(\PFUser::class);
        $user->shouldReceive('useLabFeatures')->andReturn('0');
        $project = ProjectTestBuilder::aProject()
            ->withId(101)
            ->withUsedService('plugin_agiledashboard')
            ->build();


        $planning = Mockery::mock(Planning::class);
        $planning->shouldReceive('getId')->andReturn(42);

        $tracker = Mockery::mock(\Tracker::class);
        $planning->shouldReceive('getPlanningTracker')->andReturn($tracker);
        $planning->shouldReceive('getName')->once()->andReturn("tracker name");
        $this->planning_factory->shouldReceive('getRootPlanning')->atLeast(1)->andReturn($planning);

        $this->config_manager->shouldReceive('scrumIsActivatedForProject')->once()->andReturnTrue();
        $this->config_manager->shouldReceive('getScrumTitle')->once()->andReturn("Scrum");

        $this->planning_factory->shouldReceive('getAvailablePlanningTrackers')->once()->andReturn([]);
        $this->planning_factory->shouldReceive('getPlanningsOutOfRootPlanningHierarchy')->once()->andReturn($planning);
        $this->planning_factory->shouldReceive('getPlannings')->once()->andReturn([$planning]);

        $this->event_manager->shouldReceive('processEvent')->once();
        $this->event_manager->shouldReceive('dispatch')->once();

        $this->scrum_mono_milestone_checker->shouldReceive('isScrumMonoMilestoneAvailable')->once()->andReturnFalse();
        $this->scrum_mono_milestone_checker->shouldReceive('isMonoMilestoneEnabled')->atLeast(1)->andReturnFalse();

        $this->explicit_backlog_dao->shouldReceive('isProjectUsingExplicitBacklog')->andReturnTrue();

        $this->planning_factory->shouldReceive('getPotentialPlanningTrackers')->once()->andReturn([]);

        $expected_presenter = new AdminScrumPresenter(
            [new Planning_PlanningAdminPresenter(
                $planning,
                true
            ),
            ],
            101,
            false,
            "tracker name",
            [],
            true,
            "Scrum",
            false,
            false,
            false,
            "",
            true,
            true,
            [],
            false,
            false,
        );

        $additional_sections_event = new GetAdditionalScrumAdminSection(Mockery::mock(\Project::class));

        $presenter = $this->scrum_presenter_builder->getAdminScrumPresenter($user, $project, $additional_sections_event);

        $this->assertEquals($expected_presenter, $presenter);
    }
}
