<?php
/**
 * Copyright (c) Enalean, 2014 - 2017. All rights reserved
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/
 */

require_once 'common/autoload.php';

use Tuleap\Trafficlights\Config;
use Tuleap\Trafficlights\Dao;

class TrafficlightsDataBuilder extends REST_TestDataBuilder
{
    private $tracker_factory;

    const PROJECT_TEST_MGMT_SHORTNAME = 'test-mgmt';

    const USER_TESTER_NAME   = 'rest_api_ttl_1';
    const USER_TESTER_PASS   = 'welcome0';
    const USER_TESTER_STATUS = 'A';

    public function __construct() {
        parent::__construct();

        $this->template_path   = dirname(__FILE__).'/_fixtures/';
        $this->tracker_factory = TrackerFactory::instance();
    }

    public function setUp()
    {
        $this->installPlugin();
        $this->activatePlugin('trafficlights');

        $project = $this->project_manager->getProjectByUnixName(self::PROJECT_TEST_MGMT_SHORTNAME);
        $this->importTrafficlightsTemplate($project);

        $trackers = $this->tracker_factory->getTrackersByGroupId($project->getID());

        foreach ($trackers as $tracker) {
            if ($tracker->getItemName() === 'campaign') {
                $campaign_tracker_id = $tracker->getId();
            } elseif ($tracker->getItemName() === 'test_def') {
                $test_def_tracker_id = $tracker->getId();
            } elseif($tracker->getItemName() === 'test_exec') {
                $test_exec_tracker_id = $tracker->getId();
            }
        }

        $this->configureTrafficlightsPluginForProject(
            $project,
            $campaign_tracker_id,
            $test_def_tracker_id,
            $test_exec_tracker_id
        );
    }

    private function configureTrafficlightsPluginForProject(
        Project $project,
        $campaign_tracker_id,
        $test_def_tracker_id,
        $test_exec_tracker_id
    ) {
        $config = new Config(new Dao());
        $config->setProjectConfiguration($project, $campaign_tracker_id, $test_def_tracker_id, $test_exec_tracker_id);
    }

    private function installPlugin() {
        $dbtables = new DBTablesDAO();
        $dbtables->updateFromFile(dirname(__FILE__).'/../../db/install.sql');
    }

    public function importTrafficlightsTemplate(Project $project)
    {
        echo "Import Trafficlights XML Template into project";

        $this->importTemplateInProject($project->getId(), 'tuleap_testmgmt_template.xml');

        return $this;
    }
}
