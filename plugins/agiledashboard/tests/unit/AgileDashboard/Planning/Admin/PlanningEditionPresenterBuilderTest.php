<?php
/*
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

namespace Tuleap\AgileDashboard\AgileDashboard\Planning\Admin;

use Mockery as M;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Tuleap\AgileDashboard\FormElement\Burnup;
use Tuleap\AgileDashboard\Planning\Admin\AdditionalPlanningConfigurationWarningsRetriever;
use Tuleap\AgileDashboard\Planning\Admin\PlanningEditionPresenterBuilder;
use Tuleap\AgileDashboard\Planning\Admin\PlanningWarningPossibleMisconfigurationPresenter;
use Tuleap\AgileDashboard\Planning\ScrumPlanningFilter;
use Tuleap\Test\Builders\UserTestBuilder;

final class PlanningEditionPresenterBuilderTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var PlanningEditionPresenterBuilder
     */
    private $builder;
    /**
     * @var M\LegacyMockInterface|M\MockInterface|\PlanningFactory
     */
    private $planning_factory;
    /**
     * @var \EventManager
     */
    private $event_manager;
    /**
     * @var M\LegacyMockInterface|M\MockInterface|ScrumPlanningFilter
     */
    private $scrum_planning_filter;
    /**
     * @var M\LegacyMockInterface|M\MockInterface|\PlanningPermissionsManager
     */
    private $planning_permissions_manager;
    /**
     * @var M\LegacyMockInterface|M\MockInterface|\Tracker_FormElementFactory
     */
    private $tracker_form_element_factory;

    protected function setUp(): void
    {
        $this->planning_factory             = M::mock(\PlanningFactory::class);
        $this->event_manager                = new \EventManager();
        $this->scrum_planning_filter        = M::mock(ScrumPlanningFilter::class);
        $this->planning_permissions_manager = M::mock(\PlanningPermissionsManager::class);
        $this->tracker_form_element_factory = M::mock(\Tracker_FormElementFactory::class);
        $this->builder                      = new PlanningEditionPresenterBuilder(
            $this->planning_factory,
            $this->event_manager,
            $this->scrum_planning_filter,
            $this->planning_permissions_manager,
            $this->tracker_form_element_factory
        );
    }

    public function testBuildReturnsACompletePresenter(): void
    {
        $user              = UserTestBuilder::aUser()->build();
        $milestone_tracker = M::mock(\Tracker::class);
        $milestone_tracker->shouldReceive('getId')->andReturn(127);
        $planning = new \Planning(89, 'Release planning', 109, 'Product Backlog', 'Release Plan');
        $planning->setPlanningTracker($milestone_tracker);

        $this->mockBacklogAndMilestoneTrackers();
        $this->stubCardwallConfiguration();
        $burnup = M::mock(Burnup::class);
        $burnup->shouldReceive('isUsed')->andReturnTrue();
        $this->tracker_form_element_factory->shouldReceive('getFormElementsByType')
            ->andReturn([$burnup]);

        $presenter = $this->builder->build($user, $planning);

        $this->assertSame(89, $presenter->planning_id);
        $this->assertSame(109, $presenter->project_id);
        $this->assertSame('Release planning', $presenter->planning_name);
        $this->assertSame('Product Backlog', $presenter->planning_backlog_title);
        $this->assertSame('Release Plan', $presenter->planning_plan_title);
        $this->assertNotNull($presenter->priority_change_permission);
        $this->assertNotEmpty($presenter->available_backlog_trackers);
        $this->assertNotEmpty($presenter->available_planning_trackers);
        $this->assertNotNull($presenter->cardwall_admin);
        $this->assertNotEmpty($presenter->warning_list);
        $burnup_presenter = $presenter->warning_list[0];
        $this->assertSame('/plugins/tracker?tracker=127&func=admin-formElements', $burnup_presenter->url);
        $this->assertTrue($presenter->has_warning);
    }

    public function testBuildAddsAWarningFromAnEvent(): void
    {
        $user     = UserTestBuilder::aUser()->build();
        $planning = new \Planning(89, 'Release planning', 109, 'Product Backlog', 'Release Plan');
        $this->mockBacklogAndMilestoneTrackers();
        $this->stubCardwallConfiguration();
        $burnup = M::mock(Burnup::class);
        $burnup->shouldReceive('isUsed')->andReturnTrue();
        $this->tracker_form_element_factory->shouldReceive('getFormElementsByType')
            ->andReturn([$burnup]);

        $warning_presenter = new PlanningWarningPossibleMisconfigurationPresenter(
            '/some/configuration/url',
            "Don't delete this planning"
        );
        $this->event_manager->addClosureOnEvent(
            AdditionalPlanningConfigurationWarningsRetriever::NAME,
            function (AdditionalPlanningConfigurationWarningsRetriever $event) use ($warning_presenter) {
                $event->addWarning($warning_presenter);
            }
        );

        $presenter = $this->builder->build($user, $planning);

        $this->assertContains($warning_presenter, $presenter->warning_list);
        $this->assertTrue($presenter->has_warning);
    }

    public function testBuildAddsNoWarning(): void
    {
        $user     = UserTestBuilder::aUser()->build();
        $planning = new \Planning(89, 'Release planning', 109, 'Product Backlog', 'Release Plan');
        $this->mockBacklogAndMilestoneTrackers();
        $this->stubCardwallConfiguration();
        $this->tracker_form_element_factory->shouldReceive('getFormElementsByType')
            ->andReturn([]);

        $presenter = $this->builder->build($user, $planning);

        $this->assertEmpty($presenter->warning_list);
        $this->assertFalse($presenter->has_warning);
    }

    private function mockBacklogAndMilestoneTrackers(): void
    {
        $milestone_tracker = M::mock(\Tracker::class);
        $backlog_tracker   = M::mock(\Tracker::class);
        $this->scrum_planning_filter->shouldReceive('getPlanningTrackersFiltered')
            ->once()
            ->andReturn([$milestone_tracker]);
        $this->planning_factory->shouldReceive('getAvailableBacklogTrackers')
            ->once()
            ->andReturn([$backlog_tracker]);
        $this->scrum_planning_filter->shouldReceive('getBacklogTrackersFiltered')
            ->once()
            ->andReturn([$backlog_tracker]);
        $this->planning_permissions_manager->shouldReceive('getPlanningPermissionForm')
            ->once()
            ->andReturn('Planning priority change permission form');
    }

    private function stubCardwallConfiguration(): void
    {
        $this->event_manager->addClosureOnEvent(
            AGILEDASHBOARD_EVENT_PLANNING_CONFIG,
            function ($event_name, $params) {
                $params['view'] = 'Cardwall configuration';
            }
        );
    }
}
