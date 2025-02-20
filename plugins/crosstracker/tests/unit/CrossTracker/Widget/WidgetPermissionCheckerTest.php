<?php
/**
 * Copyright (c) Enalean, 2018 - Present. All Rights Reserved.
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

namespace Tuleap\CrossTracker\Widget;

use PFUser;
use PHPUnit\Framework\MockObject\MockObject;
use ProjectManager;
use Tuleap\CrossTracker\SearchCrossTrackerWidgetStub;
use Tuleap\Dashboard\Project\ProjectDashboardController;
use Tuleap\Dashboard\User\UserDashboardController;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;

final class WidgetPermissionCheckerTest extends TestCase
{
    private ProjectManager&MockObject $project_manager;

    protected function setUp(): void
    {
        $this->project_manager = $this->createMock(ProjectManager::class);
    }

    public function testItReturnsTrueForUserCheckingItsOwnWidget(): void
    {
        $user               = UserTestBuilder::aUser()->withId(101)->build();
        $permission_checker = new WidgetPermissionChecker(
            SearchCrossTrackerWidgetStub::withExistingWidget([
                'dashboard_type' => UserDashboardController::DASHBOARD_TYPE,
                'user_id'        => 101,
            ]),
            $this->project_manager,
        );
        self::assertTrue($permission_checker->isUserWidgetAdmin($user, 1));
    }

    public function testItReturnsFalseForUserCheckingAnOtherUserWidget(): void
    {
        $user               = UserTestBuilder::aUser()->withId(200)->build();
        $permission_checker = new WidgetPermissionChecker(
            SearchCrossTrackerWidgetStub::withExistingWidget([
                'dashboard_type' => UserDashboardController::DASHBOARD_TYPE,
                'user_id'        => 101,
            ]),
            $this->project_manager,
        );
        self::assertFalse($permission_checker->isUserWidgetAdmin($user, 1));
    }

    public function testItReturnsTrueForProjectWidgetWhenUserIsAdmin(): void
    {
        $project = ProjectTestBuilder::aProject()->withId(101)->build();
        $this->project_manager->method('getProject')->willReturn($project);

        $user = $this->createMock(PFUser::class);
        $user->method('isAdmin')->willReturn(true);
        $permission_checker = new WidgetPermissionChecker(
            SearchCrossTrackerWidgetStub::withExistingWidget([
                'dashboard_type' => ProjectDashboardController::DASHBOARD_TYPE,
                'project_id'     => 101,
            ]),
            $this->project_manager,
        );
        self::assertTrue($permission_checker->isUserWidgetAdmin($user, 1));
    }

    public function testItReturnsFalseForProjectWidgetWhenUserIsNotAdmin(): void
    {
        $project = ProjectTestBuilder::aProject()->withId(101)->build();
        $this->project_manager->method('getProject')->willReturn($project);

        $user = $this->createMock(PFUser::class);
        $user->method('isAdmin')->willReturn(false);
        $permission_checker = new WidgetPermissionChecker(
            SearchCrossTrackerWidgetStub::withExistingWidget([
                'dashboard_type' => ProjectDashboardController::DASHBOARD_TYPE,
                'project_id'     => 101,
            ]),
            $this->project_manager,
        );
        self::assertFalse($permission_checker->isUserWidgetAdmin($user, 1));
    }

    public function testItReturnsFalseInOtherCase(): void
    {
        $user               = $this->createMock(PFUser::class);
        $permission_checker = new WidgetPermissionChecker(
            SearchCrossTrackerWidgetStub::withExistingWidget([]),
            $this->project_manager,
        );
        self::assertFalse($permission_checker->isUserWidgetAdmin($user, 1));
    }
}
