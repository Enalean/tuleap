<?php
/**
 * Copyright (c) Enalean, 2021-Present. All Rights Reserved.
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

namespace Tuleap\AgileDashboard\ServiceAdministration;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\MockObject\Stub;
use Tuleap\AgileDashboard\BlockScrumAccess;
use Tuleap\AgileDashboard\ExplicitBacklog\ConfigurationUpdater;
use Tuleap\AgileDashboard\Milestone\Sidebar\CheckMilestonesInSidebar;
use Tuleap\AgileDashboard\MonoMilestone\ScrumForMonoMilestoneChecker;
use Tuleap\AgileDashboard\MonoMilestone\ScrumForMonoMilestoneDisabler;
use Tuleap\AgileDashboard\MonoMilestone\ScrumForMonoMilestoneEnabler;
use Tuleap\AgileDashboard\Planning\PlanningAdministrationDelegation;
use Tuleap\AgileDashboard\Stub\Milestone\Sidebar\CheckMilestonesInSidebarStub;
use Tuleap\AgileDashboard\Stub\SplitKanbanConfigurationCheckerStub;
use Tuleap\Event\Dispatchable;
use Tuleap\GlobalResponseMock;
use Tuleap\Kanban\SplitKanbanConfigurationChecker;
use Tuleap\Layout\Feedback\NewFeedback;
use Tuleap\Test\Builders\HTTPRequestBuilder;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\Stubs\EventDispatcherStub;

final class ScrumConfigurationUpdaterTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use GlobalResponseMock;

    private const PROJECT_ID = 165;

    private ConfigurationResponse & MockObject $response;
    private EventDispatcherStub $event_dispatcher;
    private ScrumForMonoMilestoneEnabler & MockObject $mono_milestone_enabler;
    private ScrumForMonoMilestoneDisabler & MockObject $mono_milestone_disabler;
    private ConfigurationUpdater & MockObject $explicit_backlog_updater;
    private \AgileDashboard_ConfigurationManager & MockObject $config_manager;
    private ScrumForMonoMilestoneChecker & Stub $mono_milestone_checker;
    private \AgileDashboard_FirstScrumCreator & MockObject $first_scrum_creator;
    private \Project $project;

    protected function setUp(): void
    {
        $this->project  = ProjectTestBuilder::aProject()->withId(self::PROJECT_ID)->build();
        $this->response = $this->createMock(ConfigurationResponse::class);

        $this->event_dispatcher = EventDispatcherStub::withIdentityCallback();

        $this->mono_milestone_enabler   = $this->createMock(ScrumForMonoMilestoneEnabler::class);
        $this->mono_milestone_disabler  = $this->createMock(ScrumForMonoMilestoneDisabler::class);
        $this->explicit_backlog_updater = $this->createMock(ConfigurationUpdater::class);
        $this->config_manager           = $this->createMock(\AgileDashboard_ConfigurationManager::class);
        $this->mono_milestone_checker   = $this->createStub(ScrumForMonoMilestoneChecker::class);
        $this->first_scrum_creator      = $this->createMock(\AgileDashboard_FirstScrumCreator::class);
    }

    private function update(
        \HTTPRequest $request,
        SplitKanbanConfigurationChecker $split_kanban_configuration_checker,
        CheckMilestonesInSidebar $milestones_in_sidebar,
    ): void {
        $configuration_updater = new ScrumConfigurationUpdater(
            $request,
            $this->config_manager,
            $this->response,
            $this->first_scrum_creator,
            $this->mono_milestone_enabler,
            $this->mono_milestone_disabler,
            $this->mono_milestone_checker,
            $this->explicit_backlog_updater,
            $this->event_dispatcher,
            $split_kanban_configuration_checker,
            $milestones_in_sidebar,
        );
        $configuration_updater->updateConfiguration();
    }

    public function testBlockingAccessToScrumBlocksConfigurationChanges(): void
    {
        $this->event_dispatcher = EventDispatcherStub::withCallback(
            function (Dispatchable $event) {
                if ($event instanceof BlockScrumAccess) {
                    $event->disableScrumAccess();
                }
                return $event;
            }
        );
        $request                = HTTPRequestBuilder::get()->withProject($this->project)->build();

        $this->expectNotToPerformAssertions();
        $this->update(
            $request,
            SplitKanbanConfigurationCheckerStub::withoutAllowedProject(),
            CheckMilestonesInSidebarStub::withoutMilestonesInSidebar(),
        );
    }

    public function testBlockingAccessToScrumBlocksConfigurationChangesInSplitKanbanContext(): void
    {
        $this->event_dispatcher = EventDispatcherStub::withCallback(
            function (Dispatchable $event) {
                if ($event instanceof BlockScrumAccess) {
                    $event->disableScrumAccess();
                }
                return $event;
            }
        );
        $request                = HTTPRequestBuilder::get()->withProject($this->project)->build();

        $this->expectNotToPerformAssertions();
        $this->update(
            $request,
            SplitKanbanConfigurationCheckerStub::withAllowedProject(),
            CheckMilestonesInSidebarStub::withoutMilestonesInSidebar(),
        );
    }

    /**
     * @testWith [false]
     *           [true]
     */
    public function testItDoesNotUpdateSidebarConfigWhenNotPartOfTheRequest(
        bool $existing_config,
    ): void {
        $request = HTTPRequestBuilder::get()->withProject($this->project)->withParams([
            'activate-scrum' => '1',
            'activate-scrum-v2' => '1',
            'home-ease-onboarding' => false,
        ])->build();
        $this->config_manager->method('scrumIsActivatedForProject')->willReturn(true);
        $this->explicit_backlog_updater->expects(self::once())->method('updateScrumConfiguration');
        $this->config_manager
            ->expects(self::once())
            ->method('updateConfiguration')
            ->with(self::PROJECT_ID, "1", $existing_config);
        $this->mono_milestone_checker->method('isMonoMilestoneEnabled')->willReturn(false);

        $this->mono_milestone_enabler->expects(self::once())->method('enableScrumForMonoMilestones');
        $this->first_scrum_creator->expects(self::never())->method('createFirstScrum');
        $this->update(
            $request,
            SplitKanbanConfigurationCheckerStub::withoutAllowedProject(),
            $existing_config
                ? CheckMilestonesInSidebarStub::withMilestonesInSidebar()
                : CheckMilestonesInSidebarStub::withoutMilestonesInSidebar(),
        );
    }

    /**
     * @testWith ["0", false, false]
     *           ["0", true, false]
     *           ["1", false, true]
     *           ["1", true, true]
     */
    public function testItUpdatesSidebarConfigAccordinglyToRequest(
        string $submitted_value,
        bool $existing_config,
        bool $expected,
    ): void {
        $request = HTTPRequestBuilder::get()->withProject($this->project)->withParams([
            'activate-scrum' => '1',
            'should-sidebar-display-last-milestones' => $submitted_value,
            'activate-scrum-v2' => '1',
            'home-ease-onboarding' => false,
        ])->build();
        $this->config_manager->method('scrumIsActivatedForProject')->willReturn(true);
        $this->explicit_backlog_updater->expects(self::once())->method('updateScrumConfiguration');
        $this->config_manager
            ->expects(self::once())
            ->method('updateConfiguration')
            ->with(self::PROJECT_ID, "1", $expected);
        $this->mono_milestone_checker->method('isMonoMilestoneEnabled')->willReturn(false);

        $this->mono_milestone_enabler->expects(self::once())->method('enableScrumForMonoMilestones');
        $this->first_scrum_creator->expects(self::never())->method('createFirstScrum');
        $this->update(
            $request,
            SplitKanbanConfigurationCheckerStub::withoutAllowedProject(),
            $existing_config
                ? CheckMilestonesInSidebarStub::withMilestonesInSidebar()
                : CheckMilestonesInSidebarStub::withoutMilestonesInSidebar(),
        );
    }

    public function testItEnablesScrumV2MonoMilestoneMode(): void
    {
        $request = HTTPRequestBuilder::get()->withProject($this->project)->withParams([
            'activate-scrum' => '1',
            'should-sidebar-display-last-milestones' => '0',
            'activate-scrum-v2' => '1',
            'home-ease-onboarding' => false,
        ])->build();
        $this->config_manager->method('scrumIsActivatedForProject')->willReturn(true);
        $this->explicit_backlog_updater->expects(self::once())->method('updateScrumConfiguration');
        $this->config_manager->expects(self::once())->method('updateConfiguration');
        $this->mono_milestone_checker->method('isMonoMilestoneEnabled')->willReturn(false);

        $this->mono_milestone_enabler->expects(self::once())->method('enableScrumForMonoMilestones');
        $this->first_scrum_creator->expects(self::never())->method('createFirstScrum');
        $this->update(
            $request,
            SplitKanbanConfigurationCheckerStub::withoutAllowedProject(),
            CheckMilestonesInSidebarStub::withoutMilestonesInSidebar(),
        );
    }

    public function testItEnablesScrumV2MonoMilestoneModeInSplitKanbanContext(): void
    {
        $request = HTTPRequestBuilder::get()->withProject($this->project)->withParams([
            'activate-scrum' => '1',
            'should-sidebar-display-last-milestones' => '0',
            'activate-scrum-v2' => '1',
            'home-ease-onboarding' => false,
        ])->build();
        $this->config_manager->method('scrumIsActivatedForProject')->willReturn(true);
        $this->explicit_backlog_updater->expects(self::once())->method('updateScrumConfiguration');
        $this->config_manager->expects(self::once())->method('updateConfiguration');
        $this->mono_milestone_checker->method('isMonoMilestoneEnabled')->willReturn(false);

        $this->mono_milestone_enabler->expects(self::once())->method('enableScrumForMonoMilestones');
        $this->first_scrum_creator->expects(self::never())->method('createFirstScrum');
        $this->update(
            $request,
            SplitKanbanConfigurationCheckerStub::withAllowedProject(),
            CheckMilestonesInSidebarStub::withoutMilestonesInSidebar(),
        );
    }

    public function testItDisablesScrumV2MonoMilestoneMode(): void
    {
        $request = HTTPRequestBuilder::get()->withProject($this->project)->withParams([
            'activate-scrum' => '1',
            'should-sidebar-display-last-milestones' => '0',
            'activate-scrum-v2' => '0',
        ])->build();

        $this->config_manager->method('scrumIsActivatedForProject')->willReturn(true);
        $this->explicit_backlog_updater->expects(self::once())->method('updateScrumConfiguration');
        $this->config_manager->expects(self::once())->method('updateConfiguration');
        $this->mono_milestone_checker->method('isMonoMilestoneEnabled')->willReturn(true);

        $this->mono_milestone_disabler->expects(self::once())->method('disableScrumForMonoMilestones');
        $this->first_scrum_creator->expects(self::never())->method('createFirstScrum');
        $this->update(
            $request,
            SplitKanbanConfigurationCheckerStub::withoutAllowedProject(),
            CheckMilestonesInSidebarStub::withoutMilestonesInSidebar(),
        );
    }

    public function testItDisablesScrumV2MonoMilestoneModeInSplitKanbanContext(): void
    {
        $request = HTTPRequestBuilder::get()->withProject($this->project)->withParams([
            'activate-scrum' => '1',
            'should-sidebar-display-last-milestones' => '0',
            'activate-scrum-v2' => '0',
        ])->build();

        $this->config_manager->method('scrumIsActivatedForProject')->willReturn(true);
        $this->explicit_backlog_updater->expects(self::once())->method('updateScrumConfiguration');
        $this->config_manager->expects(self::once())->method('updateConfiguration');
        $this->mono_milestone_checker->method('isMonoMilestoneEnabled')->willReturn(true);

        $this->mono_milestone_disabler->expects(self::once())->method('disableScrumForMonoMilestones');
        $this->first_scrum_creator->expects(self::never())->method('createFirstScrum');
        $this->update(
            $request,
            SplitKanbanConfigurationCheckerStub::withAllowedProject(),
            CheckMilestonesInSidebarStub::withoutMilestonesInSidebar(),
        );
    }

    public function testItDiscardsActivationOfScrumV2MonoMilestoneModeFromAgileDashboardHomepage(): void
    {
        $request = HTTPRequestBuilder::get()->withProject($this->project)->withParams([
            'activate-scrum' => '1',
            'should-sidebar-display-last-milestones' => '0',
            'activate-scrum-v2' => '1',
            'home-ease-onboarding' => '1',
            'scrum-title-admin' => 'Scrum',
        ])->build();

        $this->config_manager->method('scrumIsActivatedForProject')->willReturn(false);
        $this->mono_milestone_checker->method('isMonoMilestoneEnabled')->willReturn(false);
        $this->explicit_backlog_updater->expects(self::once())->method('updateScrumConfiguration');
        $this->config_manager->expects(self::once())->method('updateConfiguration');

        $this->response->expects(self::once())->method('scrumActivated');
        $this->mono_milestone_enabler->expects(self::never())->method('enableScrumForMonoMilestones');
        $this->update(
            $request,
            SplitKanbanConfigurationCheckerStub::withoutAllowedProject(),
            CheckMilestonesInSidebarStub::withoutMilestonesInSidebar(),
        );
    }

    public function testItDiscardsActivationOfScrumV2MonoMilestoneModeFromAgileDashboardHomepageInSplitKanbanContext(): void
    {
        $request = HTTPRequestBuilder::get()->withProject($this->project)->withParams([
            'activate-scrum' => '1',
            'should-sidebar-display-last-milestones' => '0',
            'activate-scrum-v2' => '1',
            'home-ease-onboarding' => '1',
            'scrum-title-admin' => 'Scrum',
        ])->build();

        $this->config_manager->method('scrumIsActivatedForProject')->willReturn(false);
        $this->mono_milestone_checker->method('isMonoMilestoneEnabled')->willReturn(false);
        $this->explicit_backlog_updater->expects(self::once())->method('updateScrumConfiguration');
        $this->config_manager->expects(self::once())->method('updateConfiguration');

        $this->response->expects(self::never())->method('scrumActivated');
        $this->mono_milestone_enabler->expects(self::never())->method('enableScrumForMonoMilestones');
        $this->update(
            $request,
            SplitKanbanConfigurationCheckerStub::withAllowedProject(),
            CheckMilestonesInSidebarStub::withoutMilestonesInSidebar(),
        );
    }

    public function testItCreatesFirstScrum(): void
    {
        $request = HTTPRequestBuilder::get()->withProject($this->project)->withParams([
            'activate-scrum' => '1',
            'should-sidebar-display-last-milestones' => '0',
            'activate-scrum-v2' => '0',
            'scrum-title-admin' => 'Scrum',
        ])->build();

        $this->config_manager->method('scrumIsActivatedForProject')->willReturn(false);
        $this->explicit_backlog_updater->expects(self::once())->method('updateScrumConfiguration');
        $this->config_manager->expects(self::once())->method('updateConfiguration');
        $this->mono_milestone_checker->method('isMonoMilestoneEnabled')->willReturn(false);

        $this->response->expects(self::once())->method('scrumActivated');
        $this->first_scrum_creator->expects(self::once())->method('createFirstScrum')->willReturn(NewFeedback::success('yay!'));
        $GLOBALS['Response']->expects(self::once())->method('addFeedback')->with('success', 'yay!');
        $this->update(
            $request,
            SplitKanbanConfigurationCheckerStub::withoutAllowedProject(),
            CheckMilestonesInSidebarStub::withoutMilestonesInSidebar(),
        );
    }

    public function testItDoesNotCreatesFirstScrumWhenProjectUseSplitKanban(): void
    {
        $request = HTTPRequestBuilder::get()->withProject($this->project)->withParams([
            'activate-scrum' => '1',
            'should-sidebar-display-last-milestones' => '0',
            'activate-scrum-v2' => '0',
            'scrum-title-admin' => 'Scrum',
        ])->build();

        $this->config_manager->method('scrumIsActivatedForProject')->willReturn(false);
        $this->explicit_backlog_updater->expects(self::once())->method('updateScrumConfiguration');
        $this->config_manager->expects(self::once())->method('updateConfiguration');
        $this->mono_milestone_checker->method('isMonoMilestoneEnabled')->willReturn(false);

        $this->response->expects(self::never())->method('scrumActivated');
        $this->first_scrum_creator->expects(self::never())->method('createFirstScrum');
        $this->update(
            $request,
            SplitKanbanConfigurationCheckerStub::withAllowedProject(),
            CheckMilestonesInSidebarStub::withoutMilestonesInSidebar(),
        );
    }

    public function testItDoesNotCreatesFirstScrumWhenProjectUseSplitKanbanEvenIfItWasAlreadyActivated(): void
    {
        $request = HTTPRequestBuilder::get()->withProject($this->project)->withParams([
            'activate-scrum' => '1',
            'should-sidebar-display-last-milestones' => '0',
            'activate-scrum-v2' => '0',
            'scrum-title-admin' => 'Scrum',
        ])->build();

        $this->config_manager->method('scrumIsActivatedForProject')->willReturn(true);
        $this->explicit_backlog_updater->expects(self::once())->method('updateScrumConfiguration');
        $this->config_manager->expects(self::once())->method('updateConfiguration');
        $this->mono_milestone_checker->method('isMonoMilestoneEnabled')->willReturn(false);

        $this->response->expects(self::never())->method('scrumActivated');
        $this->first_scrum_creator->expects(self::never())->method('createFirstScrum');
        $this->update(
            $request,
            SplitKanbanConfigurationCheckerStub::withAllowedProject(),
            CheckMilestonesInSidebarStub::withoutMilestonesInSidebar(),
        );
    }

    public function testWhenPlanningAdministrationIsDelegatedItDoesNotCreateFirstScrum(): void
    {
        $request = HTTPRequestBuilder::get()->withProject($this->project)->withParams([
            'activate-scrum' => '1',
            'should-sidebar-display-last-milestones' => '0',
            'activate-scrum-v2' => '0',
            'scrum-title-admin' => 'Scrum',
        ])->build();

        $this->config_manager->method('scrumIsActivatedForProject')->willReturn(false);
        $this->explicit_backlog_updater->expects(self::once())->method('updateScrumConfiguration');
        $this->config_manager->expects(self::once())->method('updateConfiguration');
        $this->mono_milestone_checker->method('isMonoMilestoneEnabled')->willReturn(false);
        $this->event_dispatcher = EventDispatcherStub::withCallback(
            function (Dispatchable $event) {
                if ($event instanceof PlanningAdministrationDelegation) {
                    $event->enablePlanningAdministrationDelegation();
                }
                return $event;
            }
        );

        $this->response->expects(self::once())->method('scrumActivated');
        $this->first_scrum_creator->expects(self::never())->method('createFirstScrum');
        $this->update(
            $request,
            SplitKanbanConfigurationCheckerStub::withoutAllowedProject(),
            CheckMilestonesInSidebarStub::withoutMilestonesInSidebar(),
        );
    }

    public function testWhenPlanningAdministrationIsDelegatedItDoesNotCreateFirstScrumInSplitKanbanContext(): void
    {
        $request = HTTPRequestBuilder::get()->withProject($this->project)->withParams([
            'activate-scrum' => '1',
            'should-sidebar-display-last-milestones' => '0',
            'activate-scrum-v2' => '0',
            'scrum-title-admin' => 'Scrum',
        ])->build();

        $this->config_manager->method('scrumIsActivatedForProject')->willReturn(false);
        $this->explicit_backlog_updater->expects(self::once())->method('updateScrumConfiguration');
        $this->config_manager->expects(self::once())->method('updateConfiguration');
        $this->mono_milestone_checker->method('isMonoMilestoneEnabled')->willReturn(false);
        $this->event_dispatcher = EventDispatcherStub::withCallback(
            function (Dispatchable $event) {
                if ($event instanceof PlanningAdministrationDelegation) {
                    $event->enablePlanningAdministrationDelegation();
                }
                return $event;
            }
        );

        $this->response->expects(self::never())->method('scrumActivated');
        $this->first_scrum_creator->expects(self::never())->method('createFirstScrum');
        $this->update(
            $request,
            SplitKanbanConfigurationCheckerStub::withAllowedProject(),
            CheckMilestonesInSidebarStub::withoutMilestonesInSidebar(),
        );
    }
}
