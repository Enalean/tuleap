<?php
/**
 * Copyright (c) Enalean, 2024 - Present. All Rights Reserved.
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

namespace Tuleap\Project\Registration\Template\Upload;

use Tuleap\ForgeConfigSandbox;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Test\Stubs\Project\ActivateProjectStub;
use Tuleap\Test\Stubs\Project\NotifySiteAdminStub;

final class ProjectAfterArchiveImportActivationTest extends TestCase
{
    use ForgeConfigSandbox;

    private const PROJECT_ID = 101;

    public function testWhenConfigIsNotSetThenProjectStatusShouldBePendingAndSiteAdminShouldBeWarned(): void
    {
        $project_dao = $this->createMock(\ProjectDao::class);
        $project_dao
            ->expects(self::once())
            ->method('updateStatus')
            ->with(self::PROJECT_ID, \Project::STATUS_PENDING);

        $site_admin_notifier = NotifySiteAdminStub::build();

        $project_manager = ActivateProjectStub::build();

        $activator = new ProjectAfterArchiveImportActivation(
            $project_dao,
            $site_admin_notifier,
            $project_manager,
        );

        $activator->activateProject(ProjectTestBuilder::aProject()->withId(self::PROJECT_ID)->build());

        self::assertTrue($site_admin_notifier->isCalled());
        self::assertFalse($project_manager->isActivateWithNotificationsCalled());
        self::assertFalse($project_manager->isActivateWithoutNotificationsCalled());
    }

    public function testWhenConfigIsSetToApprovalThenProjectStatusShouldBePendingAndSiteAdminShouldBeWarned(): void
    {
        \ForgeConfig::set(\ProjectManager::CONFIG_PROJECT_APPROVAL, '1');

        $project_dao = $this->createMock(\ProjectDao::class);
        $project_dao
            ->expects(self::once())
            ->method('updateStatus')
            ->with(self::PROJECT_ID, \Project::STATUS_PENDING);

        $site_admin_notifier = NotifySiteAdminStub::build();

        $project_manager = ActivateProjectStub::build();

        $activator = new ProjectAfterArchiveImportActivation(
            $project_dao,
            $site_admin_notifier,
            $project_manager,
        );

        $activator->activateProject(ProjectTestBuilder::aProject()->withId(self::PROJECT_ID)->build());

        self::assertTrue($site_admin_notifier->isCalled());
        self::assertFalse($project_manager->isActivateWithNotificationsCalled());
        self::assertFalse($project_manager->isActivateWithoutNotificationsCalled());
    }

    public function testWhenConfigIsSetToNoApprovalThenProjectStatusShouldBeImmediatelyApprovedWithNotificationsAndSiteAdminShouldBeWarned(): void
    {
        \ForgeConfig::set(\ProjectManager::CONFIG_PROJECT_APPROVAL, '0');

        $project_dao = $this->createMock(\ProjectDao::class);
        $project_dao
            ->expects(self::never())
            ->method('updateStatus');

        $site_admin_notifier = NotifySiteAdminStub::build();

        $project_manager = ActivateProjectStub::build();

        $activator = new ProjectAfterArchiveImportActivation(
            $project_dao,
            $site_admin_notifier,
            $project_manager,
        );

        $activator->activateProject(ProjectTestBuilder::aProject()->withId(self::PROJECT_ID)->build());

        self::assertTrue($site_admin_notifier->isCalled());
        self::assertTrue($project_manager->isActivateWithNotificationsCalled());
        self::assertFalse($project_manager->isActivateWithoutNotificationsCalled());
    }
}
