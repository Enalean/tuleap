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
 *
 */

declare(strict_types=1);

namespace Tuleap\CrossTracker\REST\v1;

use EventManager;
use LogicException;
use REST_TestDataBuilder;
use Tracker;
use Tuleap\CrossTracker\CrossTrackerReportDao;
use Tuleap\Dashboard\Project\ProjectDashboardDao;
use Tuleap\Dashboard\Widget\DashboardWidgetDao;
use Tuleap\Widget\WidgetFactory;
use User_ForgeUserGroupPermissionsDao;
use User_ForgeUserGroupPermissionsManager;
use UserManager;

final class CrossTrackerDataBuilder extends REST_TestDataBuilder
{
    public function setUp(): void
    {
        $this->instanciateFactories();

        echo "Generate Cross Tracker\n";

        $report_dao = new CrossTrackerReportDao();
        $report_id  = $report_dao->createReportFromExpertQuery('');
        $report_dao->updateReport($report_id, [$this->getKanbanTracker()], '', false);

        $widget_dao = new DashboardWidgetDao(
            new WidgetFactory(
                UserManager::instance(),
                new User_ForgeUserGroupPermissionsManager(new User_ForgeUserGroupPermissionsDao()),
                EventManager::instance()
            )
        );

        $project_dashboard_dao = new ProjectDashboardDao($widget_dao);
        $dashboards            = $project_dashboard_dao->searchAllProjectDashboards(
            (int) $this->getTrackerInProjectPrivateMember(self::EPICS_TRACKER_SHORTNAME)->getProject()->getID()
        );
        if ($dashboards === []) {
            throw new LogicException('Project private member has no dashboards');
        }

        $test_user_1_id = $this->user_manager->getUserByUserName(self::TEST_USER_1_NAME)->getId();

        $user_report_id = $report_dao->createReportFromExpertQuery('');
        $report_dao->updateReport($user_report_id, [], '', false);
        $widget_dao->create($test_user_1_id, 'u', 2, 'crosstrackersearch', $user_report_id);
        $project_report_id = $report_dao->createReportFromExpertQuery('');
        $report_dao->updateReport($project_report_id, [], '', false);
        $widget_dao->create($dashboards[0]['project_id'], 'g', $dashboards[0]['id'], 'crosstrackersearch', $project_report_id);
    }

    private function getKanbanTracker(): Tracker
    {
        return $this->getTrackerInProjectPrivateMember(self::KANBAN_TRACKER_SHORTNAME);
    }
}
