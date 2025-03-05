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

use Tuleap\AgileDashboard\ConfigurationManager;
use Tuleap\AgileDashboard\ConfigurationDao;
use Tuleap\AgileDashboard\Stub\Milestone\Sidebar\DuplicateMilestonesInSidebarConfigStub;
use Tuleap\AgileDashboard\Stub\Milestone\Sidebar\UpdateMilestonesInSidebarConfigStub;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\Stubs\EventDispatcherStub;

// phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace,Squiz.Classes.ValidClassName.NotCamelCaps
#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class AgileDashboard_ConfigurationManagerTest extends \Tuleap\Test\PHPUnit\TestCase
{
    public function testScrumCanBeInAEnabledState(): void
    {
        $config_dao = $this->createMock(ConfigurationDao::class);
        $config_dao->method('isScrumActivated')->willReturn(true);

        $configuration_manager = new ConfigurationManager(
            $config_dao,
            EventDispatcherStub::withIdentityCallback(),
            DuplicateMilestonesInSidebarConfigStub::build(),
            UpdateMilestonesInSidebarConfigStub::build(),
        );

        self::assertTrue($configuration_manager->scrumIsActivatedForProject(ProjectTestBuilder::aProject()->build()));
    }

    public function testScrumIsConsideredDisabledWhenItsAccessIsBlocked(): void
    {
        $config_dao       = $this->createMock(ConfigurationDao::class);
        $event_dispatcher = new class implements \Psr\EventDispatcher\EventDispatcherInterface {
            public function dispatch(object $event)
            {
                if ($event instanceof \Tuleap\AgileDashboard\BlockScrumAccess) {
                    $event->disableScrumAccess();
                }
                return $event;
            }
        };

        $configuration_manager = new ConfigurationManager(
            $config_dao,
            $event_dispatcher,
            DuplicateMilestonesInSidebarConfigStub::build(),
            UpdateMilestonesInSidebarConfigStub::build(),
        );

        self::assertFalse($configuration_manager->scrumIsActivatedForProject(ProjectTestBuilder::aProject()->build()));
    }

    public function testDuplicateProjectConfiguration(): void
    {
        $config_dao = $this->createMock(ConfigurationDao::class);
        $config_dao->expects(self::once())->method('duplicate');

        $milestones_in_sidebar_config_duplicator = DuplicateMilestonesInSidebarConfigStub::build();

        $configuration_manager = new ConfigurationManager(
            $config_dao,
            EventDispatcherStub::withIdentityCallback(),
            $milestones_in_sidebar_config_duplicator,
            UpdateMilestonesInSidebarConfigStub::build(),
        );

        $configuration_manager->duplicate(1, 2);

        self::assertTrue($milestones_in_sidebar_config_duplicator->hasBeenCalled());
    }

    public function testUpdateConfigurationWithMilestoneInSidebar(): void
    {
        $config_dao = $this->createMock(ConfigurationDao::class);
        $config_dao->expects(self::once())->method('updateConfiguration');


        $milestones_in_sidebar_config = UpdateMilestonesInSidebarConfigStub::build();

        $configuration_manager = new ConfigurationManager(
            $config_dao,
            EventDispatcherStub::withIdentityCallback(),
            DuplicateMilestonesInSidebarConfigStub::build(),
            $milestones_in_sidebar_config,
        );

        $configuration_manager->updateConfiguration(1, true, true);

        self::assertTrue($milestones_in_sidebar_config->hasActivateBeenCalled());
        self::assertFalse($milestones_in_sidebar_config->hasDeactivateBeenCalled());
    }

    public function testUpdateConfigurationWithoutMilestoneInSidebar(): void
    {
        $config_dao = $this->createMock(ConfigurationDao::class);
        $config_dao->expects(self::once())->method('updateConfiguration');


        $milestones_in_sidebar_config = UpdateMilestonesInSidebarConfigStub::build();

        $configuration_manager = new ConfigurationManager(
            $config_dao,
            EventDispatcherStub::withIdentityCallback(),
            DuplicateMilestonesInSidebarConfigStub::build(),
            $milestones_in_sidebar_config,
        );

        $configuration_manager->updateConfiguration(1, true, false);

        self::assertFalse($milestones_in_sidebar_config->hasActivateBeenCalled());
        self::assertTrue($milestones_in_sidebar_config->hasDeactivateBeenCalled());
    }
}
