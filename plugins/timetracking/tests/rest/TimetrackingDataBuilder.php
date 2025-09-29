<?php
/**
 * Copyright Enalean (c) 2018-Present. All rights reserved.
 *
 * Tuleap and Enalean names and logos are registered trademarks owned by
 * Enalean SAS. All other trademarks or names are properties of their respective
 * owners.
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

namespace Tuleap\Timetracking\REST;

use DateTimeImmutable;
use EventManager;
use Project;
use Tracker_ArtifactFactory;
use Tuleap\Cryptography\ConcealedString;
use Tuleap\Dashboard\User\UserDashboardDao;
use Tuleap\Dashboard\User\UserDashboardRetriever;
use Tuleap\Dashboard\Widget\DashboardWidgetDao;
use Tuleap\REST\RESTTestDataBuilder;
use Tuleap\Timetracking\Time\TimeDao;
use Tuleap\Timetracking\Time\TimetrackingReportDao;
use Tuleap\Widget\WidgetFactory;
use User_ForgeUserGroupPermissionsDao;
use User_ForgeUserGroupPermissionsManager;

class TimetrackingDataBuilder extends RESTTestDataBuilder
{
    public const PROJECT_TEST_TIMETRACKING_SHORTNAME = 'test-timetracking';
    public const USER_TESTER_NAME                    = 'rest_api_timetracking_1';
    public const USER_TESTER_PASS                    = 'welcome0';

    public function __construct()
    {
        parent::__construct();
        $this->instanciateFactories();
    }

    public function setUp()
    {
        echo 'Setup Timetracking REST tests configuration' . PHP_EOL;

        $project = $this->project_manager->getProjectByUnixName(self::PROJECT_TEST_TIMETRACKING_SHORTNAME);

        $this->createUser();
        $this->addTimeOnLastMonthPeriod($project);

        $this->initProjectTimetrackingWidget();
    }

    private function createUser()
    {
        $user = $this->user_manager->getUserByUserName(self::USER_TESTER_NAME);
        $user->setPassword(new ConcealedString(self::USER_TESTER_PASS));
        $this->user_manager->updateDb($user);
    }

    private function addTimeOnLastMonthPeriod(Project $project)
    {
        $time_dao = new TimeDao();
        $user     = $this->user_manager->getUserByUserName(self::USER_TESTER_NAME);
        $trackers = $this->tracker_factory->getTrackersByGroupId(
            $project->getID()
        );

        foreach ($trackers as $tracker) {
            $artifacts = Tracker_ArtifactFactory::instance()->getArtifactsByTrackerId($tracker->getId());
            $artifact  = end($artifacts);

            $time_dao->addTime(
                $user->getId(),
                $artifact->getId(),
                (new DateTimeImmutable())->format('Y-m-d'),
                200,
                'test'
            );
        }
    }

    private function initProjectTimetrackingWidget(): void
    {
        $report_dao = new TimetrackingReportDao();
        $widget_dao = new DashboardWidgetDao(
            new WidgetFactory(
                $this->user_manager,
                new User_ForgeUserGroupPermissionsManager(new User_ForgeUserGroupPermissionsDao()),
                EventManager::instance()
            )
        );

        $user_dashboard_dao  = new UserDashboardDao($widget_dao);
        $dashboard_retriever = new UserDashboardRetriever($user_dashboard_dao);
        $user                = $this->user_manager->getUserByUserName(self::USER_TESTER_NAME);
        $dashboard_ids       = $dashboard_retriever->getAllUserDashboards($user);

        $user_report_id = $report_dao->create();
        $widget_dao->create($user->getId(), 'u', $dashboard_ids[0]->getId(), 'project-timetracking', $user_report_id);
    }
}
