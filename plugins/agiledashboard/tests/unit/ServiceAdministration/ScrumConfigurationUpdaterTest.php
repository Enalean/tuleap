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
use Tuleap\AgileDashboard\MonoMilestone\ScrumForMonoMilestoneChecker;
use Tuleap\AgileDashboard\MonoMilestone\ScrumForMonoMilestoneDisabler;
use Tuleap\AgileDashboard\MonoMilestone\ScrumForMonoMilestoneEnabler;
use Tuleap\AgileDashboard\Planning\PlanningAdministrationDelegation;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\Stubs\EventDispatcherStub;

final class ScrumConfigurationUpdaterTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private const PROJECT_ID = 165;

    private \Codendi_Request & Stub $request;
    private ConfigurationResponse & MockObject $response;
    private EventDispatcherStub $event_dispatcher;
    private ScrumForMonoMilestoneEnabler & MockObject $mono_milestone_enabler;
    private ScrumForMonoMilestoneDisabler & MockObject $mono_milestone_disabler;
    private ConfigurationUpdater & MockObject $explicit_backlog_updater;
    private \AgileDashboard_ConfigurationManager & MockObject $config_manager;
    private ScrumForMonoMilestoneChecker & Stub $mono_milestone_checker;
    private \AgileDashboard_FirstScrumCreator & MockObject $first_scrum_creator;

    protected function setUp(): void
    {
        $this->request = $this->createStub(\Codendi_Request::class);
        $project       = ProjectTestBuilder::aProject()->withId(self::PROJECT_ID)->build();
        $this->request->method('getProject')->willReturn($project);
        $this->response = $this->createMock(ConfigurationResponse::class);

        $this->event_dispatcher = EventDispatcherStub::withIdentityCallback();

        $this->mono_milestone_enabler   = $this->createMock(ScrumForMonoMilestoneEnabler::class);
        $this->mono_milestone_disabler  = $this->createMock(ScrumForMonoMilestoneDisabler::class);
        $this->explicit_backlog_updater = $this->createMock(ConfigurationUpdater::class);
        $this->config_manager           = $this->createMock(\AgileDashboard_ConfigurationManager::class);
        $this->mono_milestone_checker   = $this->createStub(ScrumForMonoMilestoneChecker::class);
        $this->first_scrum_creator      = $this->createMock(\AgileDashboard_FirstScrumCreator::class);
    }

    private function update(): void
    {
        $configuration_updater = new ScrumConfigurationUpdater(
            $this->request,
            $this->config_manager,
            $this->response,
            $this->first_scrum_creator,
            $this->mono_milestone_enabler,
            $this->mono_milestone_disabler,
            $this->mono_milestone_checker,
            $this->explicit_backlog_updater,
            $this->event_dispatcher,
            new \Tuleap\Kanban\CheckSplitKanbanConfiguration(),
        );
        $configuration_updater->updateConfiguration();
    }

    public function testBlockingAccessToScrumBlocksConfigurationChanges(): void
    {
        $this->event_dispatcher = EventDispatcherStub::withCallback(
            function (PlanningAdministrationDelegation|BlockScrumAccess $event) {
                if ($event instanceof BlockScrumAccess) {
                    $event->disableScrumAccess();
                }
                return $event;
            }
        );
        $this->request->method('get')->willReturnMap([['group_id', self::PROJECT_ID]]);

        $this->expectNotToPerformAssertions();
        $this->update();
    }

    public function testItEnablesScrumV2MonoMilestoneMode(): void
    {
        $this->request->method('get')->willReturnMap([
            ['group_id', self::PROJECT_ID],
            ['activate-scrum', '1'],
            ['activate-scrum-v2', '1'],
            ['home-ease-onboarding', false],
        ]);
        $this->config_manager->method('scrumIsActivatedForProject')->willReturn(true);
        $this->explicit_backlog_updater->expects(self::once())->method('updateScrumConfiguration');
        $this->config_manager->expects(self::once())->method('updateConfiguration');
        $this->mono_milestone_checker->method('isMonoMilestoneEnabled')->willReturn(false);

        $this->mono_milestone_enabler->expects(self::once())->method('enableScrumForMonoMilestones');
        $this->first_scrum_creator->expects(self::never())->method('createFirstScrum');
        $this->update();
    }

    public function testItDisablesScrumV2MonoMilestoneMode(): void
    {
        $this->request->method('get')->willReturnMap([
            ['group_id', self::PROJECT_ID],
            ['activate-scrum', '1'],
            ['activate-scrum-v2', '0'],
            ['home-ease-onboarding', false],
        ]);

        $this->config_manager->method('scrumIsActivatedForProject')->willReturn(true);
        $this->explicit_backlog_updater->expects(self::once())->method('updateScrumConfiguration');
        $this->config_manager->expects(self::once())->method('updateConfiguration');
        $this->mono_milestone_checker->method('isMonoMilestoneEnabled')->willReturn(true);

        $this->mono_milestone_disabler->expects(self::once())->method('disableScrumForMonoMilestones');
        $this->first_scrum_creator->expects(self::never())->method('createFirstScrum');
        $this->update();
    }

    public function testItDiscardsActivationOfScrumV2MonoMilestoneModeFromAgileDashboardHomepage(): void
    {
        $this->request->method('exist')->willReturnMap([['scrum-title-admin', true]]);
        $this->request->method('get')->willReturnMap([
            ['group_id', self::PROJECT_ID],
            ['activate-scrum', '1'],
            ['activate-scrum-v2', '1'],
            ['home-ease-onboarding', '1'],
        ]);

        $this->config_manager->method('scrumIsActivatedForProject')->willReturn(false);
        $this->mono_milestone_checker->method('isMonoMilestoneEnabled')->willReturn(false);
        $this->explicit_backlog_updater->expects(self::once())->method('updateScrumConfiguration');
        $this->config_manager->expects(self::once())->method('updateConfiguration');

        $this->response->expects(self::never())->method('scrumActivated');
        $this->mono_milestone_enabler->expects(self::never())->method('enableScrumForMonoMilestones');
        $this->update();
    }

    public function testItDiscardsFirstScrumCreation(): void
    {
        $this->request->method('exist')->willReturnMap([['scrum-title-admin', true]]);
        $this->request->method('get')->willReturnMap([
            ['group_id', self::PROJECT_ID],
            ['activate-scrum', '1'],
            ['activate-scrum-v2', '0'],
        ]);

        $this->config_manager->method('scrumIsActivatedForProject')->willReturn(false);
        $this->explicit_backlog_updater->expects(self::once())->method('updateScrumConfiguration');
        $this->config_manager->expects(self::once())->method('updateConfiguration');
        $this->mono_milestone_checker->method('isMonoMilestoneEnabled')->willReturn(false);

        $this->response->expects(self::never())->method('scrumActivated');
        $this->first_scrum_creator->expects(self::never())->method('createFirstScrum');
        $this->update();
    }

    public function testWhenPlanningAdministrationIsDelegatedItDoesNotCreateFirstScrum(): void
    {
        $this->request->method('exist')->willReturnMap([['scrum-title-admin', true]]);
        $this->request->method('get')->willReturnMap([
            ['group_id', self::PROJECT_ID],
            ['activate-scrum', '1'],
            ['activate-scrum-v2', '0'],
        ]);

        $this->config_manager->method('scrumIsActivatedForProject')->willReturn(false);
        $this->explicit_backlog_updater->expects(self::once())->method('updateScrumConfiguration');
        $this->config_manager->expects(self::once())->method('updateConfiguration');
        $this->mono_milestone_checker->method('isMonoMilestoneEnabled')->willReturn(false);
        $this->event_dispatcher = EventDispatcherStub::withCallback(
            function (PlanningAdministrationDelegation|BlockScrumAccess $event) {
                if ($event instanceof PlanningAdministrationDelegation) {
                    $event->enablePlanningAdministrationDelegation();
                }
                return $event;
            }
        );

        $this->response->expects(self::never())->method('scrumActivated');
        $this->first_scrum_creator->expects(self::never())->method('createFirstScrum');
        $this->update();
    }
}
