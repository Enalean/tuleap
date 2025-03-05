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

use EventManager;
use PHPUnit\Framework\MockObject\MockObject;
use Planning;
use Planning_Controller;
use PlanningFactory;
use PlanningPermissionsManager;
use Tracker_FormElementFactory;
use Tuleap\AgileDashboard\FormElement\Burnup;
use Tuleap\AgileDashboard\Planning\Admin\AdditionalPlanningConfigurationWarningsRetriever;
use Tuleap\AgileDashboard\Planning\Admin\ModificationBan;
use Tuleap\AgileDashboard\Planning\Admin\PlanningEditionPresenter;
use Tuleap\AgileDashboard\Planning\Admin\PlanningEditionPresenterBuilder;
use Tuleap\AgileDashboard\Planning\Admin\PlanningWarningPossibleMisconfigurationPresenter;
use Tuleap\AgileDashboard\Planning\RootPlanning\RootPlanningEditionEvent;
use Tuleap\AgileDashboard\Planning\ScrumPlanningFilter;
use Tuleap\AgileDashboard\Test\Builders\PlanningBuilder;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class PlanningEditionPresenterBuilderTest extends TestCase
{
    private const PLANNING_ID = 89;
    private const PROJECT_ID  = 109;

    private PlanningFactory&MockObject $planning_factory;
    private EventManager $event_manager;
    private ScrumPlanningFilter&MockObject $scrum_planning_filter;
    private PlanningPermissionsManager&MockObject $planning_permissions_manager;
    private Tracker_FormElementFactory&MockObject $tracker_form_element_factory;
    private Planning $planning;

    protected function setUp(): void
    {
        $this->event_manager                = new EventManager();
        $this->planning_factory             = $this->createMock(PlanningFactory::class);
        $this->scrum_planning_filter        = $this->createMock(ScrumPlanningFilter::class);
        $this->planning_permissions_manager = $this->createMock(PlanningPermissionsManager::class);
        $this->tracker_form_element_factory = $this->createMock(Tracker_FormElementFactory::class);
        $this->planning                     = PlanningBuilder::aPlanning(self::PROJECT_ID)->build();
    }

    private function buildPresenter(): PlanningEditionPresenter
    {
        $user    = UserTestBuilder::aUser()->build();
        $project = ProjectTestBuilder::aProject()->withId(self::PROJECT_ID)->build();

        $builder = new PlanningEditionPresenterBuilder(
            $this->planning_factory,
            $this->event_manager,
            $this->scrum_planning_filter,
            $this->planning_permissions_manager,
            $this->tracker_form_element_factory
        );

        return $builder->build($this->planning, $user, $project);
    }

    public function testBuildReturnsACompletePresenter(): void
    {
        $milestone_tracker = TrackerTestBuilder::aTracker()->withId(127)->build();
        $this->planning    = PlanningBuilder::aPlanning(self::PROJECT_ID)
            ->withId(self::PLANNING_ID)
            ->withName('Release planning')
            ->withBacklogTitle('Product Backlog')
            ->withPlanTitle('Release Plan')
            ->withMilestoneTracker($milestone_tracker)
            ->build();

        $this->mockBacklogAndMilestoneTrackers();
        $this->stubCardwallConfiguration();
        $burnup = $this->createMock(Burnup::class);
        $burnup->method('isUsed')->willReturn(true);
        $this->tracker_form_element_factory->method('getFormElementsByType')
            ->willReturn([$burnup]);

        $this->planning_factory->expects(self::once())->method('getRootPlanning')
            ->willReturn($this->planning);
        $this->event_manager->addClosureOnEvent(
            RootPlanningEditionEvent::NAME,
            function (RootPlanningEditionEvent $event) {
                $event->prohibitMilestoneTrackerModification(
                    new class implements ModificationBan {
                        public function getMessage(): string
                        {
                            return 'Cannot update milestone';
                        }
                    }
                );
            }
        );

        $presenter = $this->buildPresenter();

        self::assertSame(self::PLANNING_ID, $presenter->planning_id);
        self::assertSame(self::PROJECT_ID, $presenter->project_id);
        self::assertSame('Release planning', $presenter->planning_name);
        self::assertSame('Product Backlog', $presenter->planning_backlog_title);
        self::assertSame('Release Plan', $presenter->planning_plan_title);
        self::assertNotNull($presenter->priority_change_permission);
        self::assertNotEmpty($presenter->available_backlog_trackers);
        self::assertNotEmpty($presenter->available_planning_trackers);
        self::assertNotNull($presenter->cardwall_admin);
        self::assertNotEmpty($presenter->warning_list);
        $burnup_presenter = $presenter->warning_list[0];
        self::assertSame('/plugins/tracker?tracker=127&func=admin-formElements', $burnup_presenter->url);
        self::assertTrue($presenter->has_warning);
        self::assertSame('Cannot update milestone', $presenter->milestone_tracker_modification_ban->message);
    }

    public function testBuildAddsAWarningFromAnEvent(): void
    {
        $this->mockBacklogAndMilestoneTrackers();
        $this->stubCardwallConfiguration();
        $burnup = $this->createMock(Burnup::class);
        $burnup->method('isUsed')->willReturn(true);
        $this->tracker_form_element_factory->method('getFormElementsByType')->willReturn([$burnup]);
        $this->planning_factory->method('getRootPlanning')->willReturn(false);

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

        $presenter = $this->buildPresenter();

        self::assertContains($warning_presenter, $presenter->warning_list);
        self::assertTrue($presenter->has_warning);
    }

    public function testBuildAddsNoWarning(): void
    {
        $this->mockBacklogAndMilestoneTrackers();
        $this->stubCardwallConfiguration();
        $this->tracker_form_element_factory->method('getFormElementsByType')->willReturn([]);
        $this->planning_factory->method('getRootPlanning')->willReturn(false);

        $presenter = $this->buildPresenter();

        self::assertEmpty($presenter->warning_list);
        self::assertFalse($presenter->has_warning);
    }

    public function testBuildAllowsMilestoneTrackerUpdate(): void
    {
        $this->mockBacklogAndMilestoneTrackers();
        $this->stubCardwallConfiguration();
        $this->tracker_form_element_factory->method('getFormElementsByType')->willReturn([]);
        $this->planning_factory->expects(self::once())->method('getRootPlanning')->willReturn($this->planning);

        $presenter = $this->buildPresenter();

        self::assertNull($presenter->milestone_tracker_modification_ban);
    }

    private function mockBacklogAndMilestoneTrackers(): void
    {
        $milestone_tracker = TrackerTestBuilder::aTracker()->build();
        $backlog_tracker   = TrackerTestBuilder::aTracker()->build();
        $this->scrum_planning_filter->expects(self::once())->method('getPlanningTrackersFiltered')
            ->willReturn([$milestone_tracker]);
        $this->planning_factory->expects(self::once())->method('getAvailableBacklogTrackers')
            ->willReturn([$backlog_tracker]);
        $this->scrum_planning_filter->expects(self::once())->method('getBacklogTrackersFiltered')
            ->willReturn([$backlog_tracker]);
        $this->planning_permissions_manager->expects(self::once())->method('getPlanningPermissionForm')
            ->willReturn('Planning priority change permission form');
    }

    private function stubCardwallConfiguration(): void
    {
        $this->event_manager->addClosureOnEvent(
            Planning_Controller::AGILEDASHBOARD_EVENT_PLANNING_CONFIG,
            function ($event_name, $params) {
                $params['view'] = 'Cardwall configuration';
            }
        );
    }
}
