<?php
/**
 * Copyright (c) Enalean, 2014 - 2016. All rights reserved
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

    const PROJECT_TEST_MGMT_ID        = 113;
    const PROJECT_TEST_MGMT_SHORTNAME = 'test-mgmt';

    const CAMPAIGN_TRACKER_ID         = 37;
    const TEST_DEF_TRACKER_ID         = 38;
    const TEST_EXEC_TRACKER_ID        = 39;

    const USER_TESTER_NAME   = 'rest_api_ttl_1';
    const USER_TESTER_PASS   = 'welcome0';
    const USER_TESTER_STATUS = 'A';

    public function __construct() {
        parent::__construct();
        $this->template_path = dirname(__FILE__).'/_fixtures/';
    }

    public function setUp()
    {
        $this->installPlugin();
        $this->activatePlugin('trafficlights');

        $project = $this->project_manager->getProjectByUnixName(self::PROJECT_TEST_MGMT_SHORTNAME);
        $this->importTrafficlightsTemplate($project);
        $this->configureTrafficlightsPluginForProject($project);
    }

    private function configureTrafficlightsPluginForProject(Project $project) {
        $config = new Config(new Dao());
        $config->setProjectConfiguration($project, self::CAMPAIGN_TRACKER_ID, self::TEST_DEF_TRACKER_ID, self::TEST_EXEC_TRACKER_ID);
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
