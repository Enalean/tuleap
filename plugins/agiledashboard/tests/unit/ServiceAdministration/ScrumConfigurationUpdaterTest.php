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
use Tuleap\AgileDashboard\BlockScrumAccess;
use Tuleap\AgileDashboard\ConfigurationDao;
use Tuleap\AgileDashboard\ConfigurationManager;
use Tuleap\AgileDashboard\ExplicitBacklog\ConfigurationUpdater;
use Tuleap\AgileDashboard\Milestone\Sidebar\CheckMilestonesInSidebar;
use Tuleap\AgileDashboard\Milestone\Sidebar\DuplicateMilestonesInSidebarConfig;
use Tuleap\AgileDashboard\Milestone\Sidebar\UpdateMilestonesInSidebarConfig;
use Tuleap\AgileDashboard\Stub\Milestone\Sidebar\CheckMilestonesInSidebarStub;
use Tuleap\Event\Dispatchable;
use Tuleap\GlobalResponseMock;
use Tuleap\Test\Builders\HTTPRequestBuilder;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\Stubs\EventDispatcherStub;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class ScrumConfigurationUpdaterTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use GlobalResponseMock;

    private const PROJECT_ID = 165;

    private ConfigurationResponse&MockObject $response;
    private EventDispatcherStub $event_dispatcher;
    private ConfigurationUpdater&MockObject $explicit_backlog_updater;
    private ConfigurationDao&MockObject $configuration_dao;
    private UpdateMilestonesInSidebarConfig&MockObject $update_sidebar_config;
    private \Project $project;

    protected function setUp(): void
    {
        $this->project  = ProjectTestBuilder::aProject()->withId(self::PROJECT_ID)->build();
        $this->response = $this->createMock(ConfigurationResponse::class);

        $this->event_dispatcher = EventDispatcherStub::withIdentityCallback();

        $this->explicit_backlog_updater = $this->createMock(ConfigurationUpdater::class);
        $this->configuration_dao        = $this->createMock(ConfigurationDao::class);
        $this->update_sidebar_config    = $this->createMock(UpdateMilestonesInSidebarConfig::class);
    }

    private function update(
        \HTTPRequest $request,
        CheckMilestonesInSidebar $milestones_in_sidebar,
    ): void {
        $configuration_updater = new ScrumConfigurationUpdater(
            $request,
            new ConfigurationManager(
                $this->configuration_dao,
                EventDispatcherStub::withIdentityCallback(),
                $this->createMock(DuplicateMilestonesInSidebarConfig::class),
                $this->update_sidebar_config,
            ),
            $this->explicit_backlog_updater,
            $this->event_dispatcher,
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
        ])->build();
        $this->configuration_dao->method('isScrumActivated')->willReturn(true);
        $this->explicit_backlog_updater->expects(self::once())->method('updateScrumConfiguration');
        $this->configuration_dao
            ->expects(self::once())
            ->method('updateConfiguration')
            ->with(self::PROJECT_ID, true);

        if ($existing_config) {
            $this->update_sidebar_config->expects(self::once())->method('activateMilestonesInSidebar');
        } else {
            $this->update_sidebar_config->expects(self::once())->method('deactivateMilestonesInSidebar');
        }

        $this->update(
            $request,
            $existing_config
                ? CheckMilestonesInSidebarStub::withMilestonesInSidebar()
                : CheckMilestonesInSidebarStub::withoutMilestonesInSidebar(),
        );
    }

    /**
     * @testWith ["0", false]
     *           ["0", true]
     *           ["1", false]
     *           ["1", true]
     */
    public function testItUpdatesSidebarConfigAccordinglyToRequest(
        string $submitted_value,
        bool $existing_config,
    ): void {
        $request = HTTPRequestBuilder::get()->withProject($this->project)->withParams([
            'activate-scrum'                         => '1',
            'should-sidebar-display-last-milestones' => $submitted_value,
        ])->build();
        $this->configuration_dao->method('isScrumActivated')->willReturn(true);
        $this->explicit_backlog_updater->expects(self::once())->method('updateScrumConfiguration');
        $this->configuration_dao
            ->expects(self::once())
            ->method('updateConfiguration')
            ->with(self::PROJECT_ID, true);

        if ($submitted_value === '0') {
            $this->update_sidebar_config->expects(self::once())->method('deactivateMilestonesInSidebar');
        } else {
            $this->update_sidebar_config->expects(self::once())->method('activateMilestonesInSidebar');
        }

        $this->update(
            $request,
            $existing_config
                ? CheckMilestonesInSidebarStub::withMilestonesInSidebar()
                : CheckMilestonesInSidebarStub::withoutMilestonesInSidebar(),
        );
    }
}
