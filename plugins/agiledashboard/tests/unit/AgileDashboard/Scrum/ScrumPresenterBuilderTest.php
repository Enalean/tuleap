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

use EventManager;
use PHPUnit\Framework\MockObject\MockObject;
use Planning_PlanningAdminPresenter;
use PlanningFactory;
use Tuleap\AgileDashboard\AdminScrumPresenter;
use Tuleap\AgileDashboard\ConfigurationDao;
use Tuleap\AgileDashboard\ConfigurationManager;
use Tuleap\AgileDashboard\Event\GetAdditionalScrumAdminSection;
use Tuleap\AgileDashboard\ExplicitBacklog\ExplicitBacklogDao;
use Tuleap\AgileDashboard\Milestone\Sidebar\DuplicateMilestonesInSidebarConfig;
use Tuleap\AgileDashboard\Milestone\Sidebar\UpdateMilestonesInSidebarConfig;
use Tuleap\AgileDashboard\Stub\Milestone\Sidebar\CheckMilestonesInSidebarStub;
use Tuleap\AgileDashboard\Test\Builders\PlanningBuilder;
use Tuleap\AgileDashboard\Workflow\AddToTopBacklogPostActionDao;
use Tuleap\GlobalLanguageMock;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Test\Stubs\EventDispatcherStub;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class ScrumPresenterBuilderTest extends TestCase
{
    use GlobalLanguageMock;

    private ScrumPresenterBuilder $scrum_presenter_builder;
    private ExplicitBacklogDao&MockObject $explicit_backlog_dao;
    private PlanningFactory&MockObject $planning_factory;
    private ConfigurationDao&MockObject $configuration_dao;

    protected function setUp(): void
    {
        $this->configuration_dao            = $this->createMock(ConfigurationDao::class);
        $event_manager                      = $this->createMock(EventManager::class);
        $this->planning_factory             = $this->createMock(PlanningFactory::class);
        $this->explicit_backlog_dao         = $this->createMock(ExplicitBacklogDao::class);
        $add_to_top_backlog_post_action_dao = $this->createMock(AddToTopBacklogPostActionDao::class);

        $this->scrum_presenter_builder = new ScrumPresenterBuilder(
            new ConfigurationManager(
                $this->configuration_dao,
                EventDispatcherStub::withIdentityCallback(),
                $this->createMock(DuplicateMilestonesInSidebarConfig::class),
                $this->createMock(UpdateMilestonesInSidebarConfig::class),
            ),
            $event_manager,
            $this->planning_factory,
            $this->explicit_backlog_dao,
            $add_to_top_backlog_post_action_dao,
            CheckMilestonesInSidebarStub::withoutMilestonesInSidebar(),
        );

        $add_to_top_backlog_post_action_dao->method('isAtLeastOnePostActionDefinedInProject')->willReturn(true);
        $event_manager->expects($this->once())->method('processEvent');
        $event_manager->expects($this->once())->method('dispatch');
    }

    public function testItBuildsPresenterWhenNoRootPlanning(): void
    {
        $user    = UserTestBuilder::buildWithDefaults();
        $project = ProjectTestBuilder::aProject()
            ->withId(101)
            ->withUsedService('plugin_agiledashboard')
            ->build();

        $this->planning_factory->expects($this->atLeastOnce())->method('getRootPlanning')->willReturn(false);

        $this->configuration_dao->expects($this->once())->method('isScrumActivated')->willReturn(false);

        $planning = PlanningBuilder::aPlanning(101)->withId(42)->build();
        $this->planning_factory->expects($this->once())->method('getPlanningsOutOfRootPlanningHierarchy')->willReturn($planning);
        $this->planning_factory->expects($this->once())->method('getPlannings')->willReturn([$planning]);

        $this->explicit_backlog_dao->method('isProjectUsingExplicitBacklog')->willReturn(false);

        $expected_presenter = new AdminScrumPresenter(
            [new Planning_PlanningAdminPresenter(
                $planning,
                false
            ),
            ],
            101,
            true,
            '',
            [],
            false,
            true,
            '',
            false,
            true,
            [],
            false,
            false,
        );

        $additional_sections_event = new GetAdditionalScrumAdminSection($project);

        $presenter = $this->scrum_presenter_builder->getAdminScrumPresenter($user, $project, $additional_sections_event);

        self::assertEquals($expected_presenter, $presenter);
    }

    public function testItBuildsPresenterInExplicitBacklogContext(): void
    {
        $user    = UserTestBuilder::buildWithDefaults();
        $project = ProjectTestBuilder::aProject()
            ->withId(101)
            ->withUsedService('plugin_agiledashboard')
            ->build();


        $tracker  = TrackerTestBuilder::aTracker()->build();
        $planning = PlanningBuilder::aPlanning(101)
            ->withId(42)
            ->withMilestoneTracker($tracker)
            ->withName('tracker name')
            ->build();
        $this->planning_factory->expects($this->atLeastOnce())->method('getRootPlanning')->willReturn($planning);

        $this->configuration_dao->expects($this->once())->method('isScrumActivated')->willReturn(true);

        $this->planning_factory->expects($this->once())->method('getAvailablePlanningTrackers')->willReturn([]);
        $this->planning_factory->expects($this->once())->method('getPlanningsOutOfRootPlanningHierarchy')->willReturn($planning);
        $this->planning_factory->expects($this->once())->method('getPlannings')->willReturn([$planning]);

        $this->explicit_backlog_dao->method('isProjectUsingExplicitBacklog')->willReturn(true);

        $this->planning_factory->expects($this->once())->method('getPotentialPlanningTrackers')->willReturn([]);

        $expected_presenter = new AdminScrumPresenter(
            [new Planning_PlanningAdminPresenter(
                $planning,
                true
            ),
            ],
            101,
            false,
            'tracker name',
            [],
            true,
            false,
            '',
            true,
            true,
            [],
            false,
            false,
        );

        $additional_sections_event = new GetAdditionalScrumAdminSection($project);

        $presenter = $this->scrum_presenter_builder->getAdminScrumPresenter($user, $project, $additional_sections_event);

        self::assertEquals($expected_presenter, $presenter);
    }
}
