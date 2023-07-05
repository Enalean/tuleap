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
use Tuleap\CrossTracker\CrossTrackerReportDao;
use Tuleap\Dashboard\Project\ProjectDashboardController;
use Tuleap\Dashboard\User\UserDashboardController;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\Builders\UserTestBuilder;

final class WidgetPermissionCheckerTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private WidgetPermissionChecker $permission_checker;
    private ProjectManager&MockObject $project_manager;
    private CrossTrackerReportDao&MockObject $cross_tracker_dao;

    protected function setUp(): void
    {
        parent::setUp();

        $this->cross_tracker_dao = $this->createMock(CrossTrackerReportDao::class);
        $this->project_manager   = $this->createMock(ProjectManager::class);

        $this->permission_checker = new WidgetPermissionChecker($this->cross_tracker_dao, $this->project_manager);
    }

    public function testItReturnsTrueForUserCheckingItsOwnWidget(): void
    {
        $this->cross_tracker_dao->method("searchCrossTrackerWidgetByCrossTrackerReportId")->willReturn(
            [
                'dashboard_type' => UserDashboardController::DASHBOARD_TYPE,
                'user_id'        => 101,
            ]
        );

        $user = UserTestBuilder::aUser()->withId(101)->build();

        self::assertTrue($this->permission_checker->isUserWidgetAdmin($user, 1));
    }

    public function testItReturnsFalseForUserCheckingAnOtherUserWidget(): void
    {
        $this->cross_tracker_dao->method("searchCrossTrackerWidgetByCrossTrackerReportId")->willReturn(
            [
                'dashboard_type' => UserDashboardController::DASHBOARD_TYPE,
                'user_id'        => 101,
            ]
        );

        $user = UserTestBuilder::aUser()->withId(200)->build();

        self::assertFalse($this->permission_checker->isUserWidgetAdmin($user, 1));
    }

    public function testItReturnsTrueForProjectWidgetWhenUserIsAdmin(): void
    {
        $this->cross_tracker_dao->method("searchCrossTrackerWidgetByCrossTrackerReportId")->willReturn(
            [
                'dashboard_type' => ProjectDashboardController::DASHBOARD_TYPE,
                'project_id'     => 101,
            ]
        );
        $project = ProjectTestBuilder::aProject()->withId(101)->build();
        $this->project_manager->method("getProject")->willReturn($project);

        $user = $this->createMock(PFUser::class);
        $user->method('isAdmin')->willReturn(true);

        self::assertTrue($this->permission_checker->isUserWidgetAdmin($user, 1));
    }

    public function testItReturnsFalseForProjectWidgetWhenUserIsNotAdmin(): void
    {
        $this->cross_tracker_dao->method("searchCrossTrackerWidgetByCrossTrackerReportId")->willReturn(
            [
                'dashboard_type' => ProjectDashboardController::DASHBOARD_TYPE,
                'project_id'     => 101,
            ]
        );
        $project = ProjectTestBuilder::aProject()->withId(101)->build();
        $this->project_manager->method("getProject")->willReturn($project);

        $user = $this->createMock(PFUser::class);
        $user->method('isAdmin')->willReturn(false);

        self::assertFalse($this->permission_checker->isUserWidgetAdmin($user, 1));
    }

    public function testItReturnsFalseInOtherCase(): void
    {
        $this->cross_tracker_dao->method("searchCrossTrackerWidgetByCrossTrackerReportId")->willReturn([]);
        $user = $this->createMock(PFUser::class);
        self::assertFalse($this->permission_checker->isUserWidgetAdmin($user, 1));
    }
}
