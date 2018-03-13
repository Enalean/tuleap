<?php
/**
 * Copyright Enalean (c) 2018. All rights reserved.
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

use Tuleap\Timetracking\Time\TimeDao;
use Tuleap\Timetracking\Time\TimeUpdater;

class TimetrackingDataBuilder extends REST_TestDataBuilder
{
    const PROJECT_TEST_TIMETRACKING_SHORTNAME = 'test-timetracking';
    const TRACKER_SHORTNAME                   = 'timetracking_testing';
    const USER_TESTER_NAME                    = 'rest_api_timetracking_1';
    const USER_TESTER_PASS                    = 'welcome0';
    const USER_TESTER_STATUS                  = 'A';

    public function __construct()
    {
        parent::__construct();
        $this->instanciateFactories();
    }

    public function setUp()
    {
        echo 'Setup Timetracking REST tests configuration';

        $this->installPlugin();
        $this->activatePlugin('timetracking');

        $this->createUser();
        $this->addTimesInDB();
    }

    private function createUser()
    {
        $user = $this->user_manager->getUserByUserName(self::USER_TESTER_NAME);
        $user->setPassword(self::USER_TESTER_PASS);
        $this->user_manager->updateDb($user);
    }

    private function installPlugin()
    {
        $dbtables = new DBTablesDAO();
        $dbtables->updateFromFile(dirname(__FILE__) . '/../../db/install.sql');
    }

    private function addTimesInDB()
    {
        $time_updater = new TimeUpdater(
            new TimeDao()
        );

        $user    = $this->user_manager->getUserByUserName(self::USER_TESTER_NAME);
        $project = $this->project_manager->getProjectByUnixName(self::PROJECT_TEST_TIMETRACKING_SHORTNAME);
        $tracker = $this->tracker_factory->getTrackerByShortnameAndProjectId(
            self::TRACKER_SHORTNAME,
            $project->getID()
        );

        $artifacts = Tracker_ArtifactFactory::instance()->getArtifactsByTrackerId($tracker->getId());

        foreach ($artifacts as $artifact) {
            $time_updater->addTimeForUserInArtifact(
                $user,
                $artifact,
                date('Y-m-d', $artifact->getSubmittedOn()),
                '10:00',
                'test'
            );
        }
    }
}
