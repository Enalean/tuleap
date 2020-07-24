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
 *
 */

namespace Tuleap\CrossTracker;

use EventManager;
use REST_TestDataBuilder;
use Tuleap\Dashboard\Widget\DashboardWidgetDao;
use Tuleap\Widget\WidgetFactory;
use User_ForgeUserGroupPermissionsDao;
use User_ForgeUserGroupPermissionsManager;
use UserManager;

class CrossTrackerDataBuilder extends REST_TestDataBuilder
{
    public function setUp()
    {
        $this->instanciateFactories();

        echo "Generate Cross Tracker\n";

        $cross_tracker_saver = new CrossTrackerReportDao();
        $report_id           = $cross_tracker_saver->create();
        $cross_tracker_saver->addTrackersToReport([$this->getKanbanTracker()], $report_id);

        $widget_dao = new DashboardWidgetDao(
            new WidgetFactory(
                UserManager::instance(),
                new User_ForgeUserGroupPermissionsManager(new User_ForgeUserGroupPermissionsDao()),
                EventManager::instance()
            )
        );

        $test_user_1_id = $this->user_manager->getUserByUserName(self::TEST_USER_1_NAME)->getId();

        $user_report_id  = $cross_tracker_saver->create();
        $widget_dao->create($test_user_1_id, 'u', 2, 'crosstrackersearch', $user_report_id);
        $project_report_id  = $cross_tracker_saver->create();
        $widget_dao->create($test_user_1_id, 'g', 3, 'crosstrackersearch', $project_report_id);
    }

    /**
     * @return \Tracker
     */
    private function getKanbanTracker()
    {
        return $this->getTrackerInProjectPrivateMember(self::KANBAN_TRACKER_SHORTNAME);
    }
}
