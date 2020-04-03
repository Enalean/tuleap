<?php
/**
 * Copyright (c) Enalean, 2018. All Rights Reserved.
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

use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PFUser;
use PHPUnit\Framework\TestCase;
use Project;
use ProjectManager;
use Tuleap\CrossTracker\CrossTrackerReportDao;
use Tuleap\Dashboard\Project\ProjectDashboardController;
use Tuleap\Dashboard\User\UserDashboardController;

require_once __DIR__ . '/../../bootstrap.php';

class WidgetPermissionCheckerTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var WidgetPermissionChecker
     */
    private $permission_checker;
    /**
     * @var ProjectManager
     */
    private $project_manager;
    /**
     * @var CrossTrackerReportDao|Mockery\MockInterface
     */
    private $cross_tracker_dao;

    protected function setUp(): void
    {
        parent::setUp();

        $this->cross_tracker_dao = Mockery::mock(CrossTrackerReportDao::class);
        $this->project_manager   = Mockery::mock(ProjectManager::class);

        $this->permission_checker = new WidgetPermissionChecker($this->cross_tracker_dao, $this->project_manager);
    }

    public function testItReturnsTrueForUserCheckingItsOwnWidget()
    {
        $this->cross_tracker_dao->shouldReceive("searchCrossTrackerWidgetByCrossTrackerReportId")->andReturns(
            [
                'dashboard_type' => UserDashboardController::DASHBOARD_TYPE,
                'user_id'        => 101
            ]
        );

        $user = Mockery::mock(PFUser::class);
        $user->shouldReceive("getId")->andReturn(101);

        $this->assertTrue($this->permission_checker->isUserWidgetAdmin($user, 1));
    }

    public function testItReturnsFalseForUserCheckingAnOtherUserWidget()
    {
        $this->cross_tracker_dao->shouldReceive("searchCrossTrackerWidgetByCrossTrackerReportId")->andReturns(
            [
                'dashboard_type' => UserDashboardController::DASHBOARD_TYPE,
                'user_id'        => 101
            ]
        );

        $user = Mockery::mock(PFUser::class);
        $user->shouldReceive("getId")->andReturn(200);

        $this->assertFalse($this->permission_checker->isUserWidgetAdmin($user, 1));
    }

    public function testItReturnsTrueForProjectWidgetWhenUserIsAdmin()
    {
        $this->cross_tracker_dao->shouldReceive("searchCrossTrackerWidgetByCrossTrackerReportId")->andReturns(
            [
                'dashboard_type' => ProjectDashboardController::DASHBOARD_TYPE,
                'project_id'     => 101
            ]
        );
        $project = Mockery::mock(Project::class);
        $project->shouldReceive('getID')->andReturn("101");
        $this->project_manager->shouldReceive("getProject")->andReturn($project);

        $user = Mockery::mock(PFUser::class);
        $user->shouldReceive('isAdmin')->andReturn(true);

        $this->assertTrue($this->permission_checker->isUserWidgetAdmin($user, 1));
    }

    public function testItReturnsFalseForProjectWidgetWhenUserIsNotAdmin()
    {
        $this->cross_tracker_dao->shouldReceive("searchCrossTrackerWidgetByCrossTrackerReportId")->andReturns(
            [
                'dashboard_type' => ProjectDashboardController::DASHBOARD_TYPE,
                'project_id'     => 101
            ]
        );
        $project = Mockery::mock(Project::class);
        $project->shouldReceive('getID')->andReturn("101");
        $this->project_manager->shouldReceive("getProject")->andReturn($project);

        $user = Mockery::mock(PFUser::class);
        $user->shouldReceive('isAdmin')->andReturn(false);

        $this->assertFalse($this->permission_checker->isUserWidgetAdmin($user, 1));
    }

    public function testItReturnsFalseInOtherCase()
    {
        $this->cross_tracker_dao->shouldReceive("searchCrossTrackerWidgetByCrossTrackerReportId")->andReturns([]);
        $user = Mockery::mock(PFUser::class);
        $this->assertFalse($this->permission_checker->isUserWidgetAdmin($user, 1));
    }
}
