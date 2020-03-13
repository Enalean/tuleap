<?php
/**
 * Copyright Enalean (c) 2018-2019. All rights reserved.
 *
 * Tuleap and Enalean names and logos are registrated trademarks owned by
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

use DBTablesDao;
use EventManager;
use PFUser;
use Project;
use REST_TestDataBuilder;
use Tracker_ArtifactFactory;
use Tuleap\Dashboard\User\UserDashboardDao;
use Tuleap\Dashboard\User\UserDashboardRetriever;
use Tuleap\Dashboard\Widget\DashboardWidgetDao;
use Tuleap\Timetracking\Admin\AdminDao;
use Tuleap\Timetracking\Admin\TimetrackingEnabler;
use Tuleap\Timetracking\Admin\TimetrackingUgroupDao;
use Tuleap\Timetracking\Admin\TimetrackingUgroupSaver;
use Tuleap\Timetracking\Time\TimeDao;
use Tuleap\Timetracking\Time\TimetrackingReportDao;
use Tuleap\Widget\WidgetFactory;
use User_ForgeUserGroupPermissionsDao;
use User_ForgeUserGroupPermissionsManager;

class TimetrackingDataBuilder extends REST_TestDataBuilder
{
    public const PROJECT_TEST_TIMETRACKING_SHORTNAME = 'test-timetracking';
    public const TRACKER_SHORTNAME                   = 'timetracking_testing';
    public const USER_TESTER_NAME                    = 'rest_api_timetracking_1';
    public const USER_TESTER_PASS                    = 'welcome0';
    public const USER_TESTER_STATUS                  = 'A';

    public function __construct()
    {
        parent::__construct();
        $this->instanciateFactories();
    }

    public function setUp()
    {
        echo 'Setup Timetracking REST tests configuration' . PHP_EOL;

        $this->installPlugin();
        $this->activatePlugin('timetracking');

        $project = $this->project_manager->getProjectByUnixName(self::PROJECT_TEST_TIMETRACKING_SHORTNAME);

        $this->createUser();
        $this->setEnabledTrackers($project);
        $this->setWritersAndReaders($project);
        $this->addTimesInDB($project);

        $this->initTimetrackingOverviewWidget();
    }

    private function createUser()
    {
        $user = $this->user_manager->getUserByUserName(self::USER_TESTER_NAME);
        $user->setPassword(self::USER_TESTER_PASS);
        $this->user_manager->updateDb($user);
    }

    private function installPlugin()
    {
        $dbtables = new DBTablesDao();
        $dbtables->updateFromFile(dirname(__FILE__) . '/../../db/install.sql');
    }

    private function addTimesInDB(Project $project)
    {
        $user     = $this->user_manager->getUserByUserName(self::USER_TESTER_NAME);
        $trackers = $this->tracker_factory->getTrackersByGroupId(
            $project->getID()
        );

        foreach ($trackers as $tracker) {
            $artifacts = Tracker_ArtifactFactory::instance()->getArtifactsByTrackerId($tracker->getId());

            $this->addTimes($artifacts, $user);
        }
    }

    /**
     * @param Tracker_Artifact[] $artifacts
     */
    private function addTimes(array $artifacts, PFUser $user)
    {
        $time_dao = new TimeDao();

        foreach ($artifacts as $artifact) {
            $this->addTimesIn2018($time_dao, $artifact, $user);
        }

        $this->addTimeOnLastMonthPeriod($time_dao, $artifact, $user);
    }

    private function addTimesIn2018(TimeDao $time_dao, \Tracker_Artifact $artifact, PFUser $user)
    {
        $time_dao->addTime(
            $user->getId(),
            $artifact->getId(),
            '2018-04-01',
            600,
            'test'
        );
    }

    private function addTimeOnLastMonthPeriod(
        TimeDao $time_dao,
        \Tracker_Artifact $artifact,
        PFUser $user
    ) {
        $time_dao->addTime(
            $user->getId(),
            $artifact->getId(),
            date('Y-m-d', $_SERVER['REQUEST_TIME']),
            200,
            'test'
        );
    }

    private function setEnabledTrackers(Project $project)
    {
        $enabler = new TimetrackingEnabler(
            new AdminDao()
        );

        $tracker = $this->tracker_factory->getTrackerByShortnameAndProjectId(
            self::TRACKER_SHORTNAME,
            $project->getID()
        );

        $enabler->enableTimetrackingForTracker($tracker);
    }

    private function setWritersAndReaders(Project $project)
    {
        $saver   = new TimetrackingUgroupSaver(new TimetrackingUgroupDao());
        $tracker = $this->tracker_factory->getTrackerByShortnameAndProjectId(
            self::TRACKER_SHORTNAME,
            $project->getID()
        );

        $saver->saveWriters($tracker, [\ProjectUGroup::PROJECT_MEMBERS]);
        $saver->saveReaders($tracker, [\ProjectUGroup::PROJECT_ADMIN]);
    }

    private function initTimetrackingOverviewWidget()
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
        $widget_dao->create($user->getId(), 'u', $dashboard_ids[0]->getId(), 'timetracking-overview', $user_report_id);
    }
}
