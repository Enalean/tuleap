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
    private \Codendi_Request & Stub $request;
    private ConfigurationResponse & MockObject $response;
    private EventDispatcherStub $event_dispatcher;

    protected function setUp(): void
    {
        $this->request = $this->createStub(\Codendi_Request::class);
        $project       = ProjectTestBuilder::aProject()->build();
        $this->request->method('get')->willReturnMap(['group_id' => $project->getID()]);
        $this->request->method('getProject')->willReturn($project);
        $this->response = $this->createMock(ConfigurationResponse::class);
    }

    private function update(): void
    {
        $configuration_updater = new ScrumConfigurationUpdater(
            $this->request,
            $this->createStub(\AgileDashboard_ConfigurationManager::class),
            $this->response,
            $this->createStub(\AgileDashboard_FirstScrumCreator::class),
            $this->createStub(ScrumForMonoMilestoneEnabler::class),
            $this->createStub(ScrumForMonoMilestoneDisabler::class),
            $this->createStub(ScrumForMonoMilestoneChecker::class),
            $this->createStub(ConfigurationUpdater::class),
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

        $this->response->expects(self::never())->method('scrumConfigurationUpdated');
        $this->update();
    }
}
