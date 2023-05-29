<?php
/**
 * Copyright (c) Enalean, 2023-Present. All Rights Reserved.
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

namespace Tuleap\Project\Admin\Visibility;

use ForgeAccess;
use ForgeConfig;
use Project;
use Psr\EventDispatcher\EventDispatcherInterface;
use Tuleap\ForgeConfigSandbox;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;

class UpdateVisibilityCheckerTest extends TestCase
{
    use ForgeConfigSandbox;

    public function testVisibilitySwitchIsAllowedIfPlatformDoesNotAllowRestrictedUsers(): void
    {
        ForgeConfig::set(ForgeAccess::CONFIG, "whatever");
        $project = ProjectTestBuilder::aProject()->build();

        self::assertTrue(
            $this->buildDefaultUpdateVisibilityChecker()->canUpdateVisibilityRegardingRestrictedUsers($project, 'whatever')->canSwitch()
        );
    }

    public function testVisibilitySwitchIsAllowedIfPlatformAllowRestrictedUsersButVisibilityIsNotPrivateWithoutRestricted(): void
    {
        ForgeConfig::set(ForgeAccess::CONFIG, ForgeAccess::RESTRICTED);
        $project = ProjectTestBuilder::aProject()->build();

        self::assertTrue(
            $this->buildDefaultUpdateVisibilityChecker()->canUpdateVisibilityRegardingRestrictedUsers($project, Project::ACCESS_PUBLIC)->canSwitch()
        );

        self::assertTrue(
            $this->buildDefaultUpdateVisibilityChecker()->canUpdateVisibilityRegardingRestrictedUsers($project, Project::ACCESS_PRIVATE)->canSwitch()
        );

        self::assertTrue(
            $this->buildDefaultUpdateVisibilityChecker()->canUpdateVisibilityRegardingRestrictedUsers($project, Project::ACCESS_PUBLIC_UNRESTRICTED)->canSwitch()
        );
    }

    public function testVisibilitySwitchIsAllowedIfPlatformAllowRestrictedUsersVisibilityIsPrivateWithoutRestrictedWithActiveAdminsAndExternalPluginsAllowTheChange(): void
    {
        ForgeConfig::set(ForgeAccess::CONFIG, ForgeAccess::RESTRICTED);
        $project = $this->createMock(Project::class);
        $project->method('getAdmins')->willReturn([
            UserTestBuilder::anActiveUser()->build(),
        ]);

        $checker = new UpdateVisibilityChecker(
            new class implements EventDispatcherInterface {
                /**
                 * @return UpdateVisibilityIsAllowedEvent
                 */
                public function dispatch(object $event)
                {
                    return new UpdateVisibilityIsAllowedEvent(ProjectTestBuilder::aProject()->build());
                }
            }
        );

        self::assertTrue(
            $checker->canUpdateVisibilityRegardingRestrictedUsers($project, Project::ACCESS_PRIVATE_WO_RESTRICTED)->canSwitch()
        );
    }

    public function testVisibilitySwitchIsNotAllowedIfPlatformAllowRestrictedUsersVisibilityIsPrivateWithoutRestrictedWithActiveAdminsAndExternalPluginsDoesNotAllowTheChange(): void
    {
        ForgeConfig::set(ForgeAccess::CONFIG, ForgeAccess::RESTRICTED);
        $project = $this->createMock(Project::class);
        $project->method('getAdmins')->willReturn([
            UserTestBuilder::anActiveUser()->build(),
        ]);

        $checker = new UpdateVisibilityChecker(
            new class implements EventDispatcherInterface {
                /**
                 * @return UpdateVisibilityIsAllowedEvent
                 */
                public function dispatch(object $event)
                {
                    $event = new UpdateVisibilityIsAllowedEvent(ProjectTestBuilder::aProject()->build());
                    $event->setUpdateVisibilityStatus(
                        UpdateVisibilityStatus::buildStatusSwitchIsNotAllowed(""),
                    );

                    return $event;
                }
            }
        );

        self::assertFalse(
            $checker->canUpdateVisibilityRegardingRestrictedUsers($project, Project::ACCESS_PRIVATE_WO_RESTRICTED)->canSwitch()
        );
    }

    public function testVisibilitySwitchIsNotAllowedIfPlatformAllowRestrictedUsersVisibilityIsPrivateWithoutRestrictedWithoutActiveAdmins(): void
    {
        ForgeConfig::set(ForgeAccess::CONFIG, ForgeAccess::RESTRICTED);
        $project = $this->createMock(Project::class);
        $project->method('getAdmins')->willReturn([
            UserTestBuilder::aRestrictedUser()->build(),
        ]);

        self::assertFalse(
            $this->buildDefaultUpdateVisibilityChecker()->canUpdateVisibilityRegardingRestrictedUsers($project, Project::ACCESS_PRIVATE_WO_RESTRICTED)->canSwitch()
        );
    }

    private function buildDefaultUpdateVisibilityChecker(): UpdateVisibilityChecker
    {
        return new UpdateVisibilityChecker(
            new class implements EventDispatcherInterface {
                /**
                 * @return void
                 */
                public function dispatch(object $event)
                {
                    return;
                }
            }
        );
    }
}
