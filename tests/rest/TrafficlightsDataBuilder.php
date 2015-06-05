<?php
/**
 * Copyright (c) Enalean, 2014 - 2015. All rights reserved
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

class TrafficlightsDataBuilder extends REST_TestDataBuilder {

    const PROJECT_TEST_MGMT_ID        = 109;
    const PROJECT_TEST_MGMT_SHORTNAME = 'test-mgmt';

    const CAMPAIGN_TRACKER_ID         = 26;
    const TEST_DEF_TRACKER_ID         = 27;
    const TEST_EXEC_TRACKER_ID        = 28;

    const USER_TESTER_NAME   = 'user_tester';
    const USER_TESTER_PASS   = 'welcome0';
    const USER_TESTER_STATUS = 'A';

    public function __construct() {
        parent::__construct();
        $this->template_path = dirname(__FILE__).'/_fixtures/';
    }

    public function setUp() {
        $this->installPlugin();
        $this->activatePlugin('trafficlights');
        $project = $this->generateProject();
        $this->importTrafficlightsTemplate();
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

    public function importTrafficlightsTemplate() {
        echo "Import Trafficlights XML Template\n";

        $this->importTemplateInProject(self::PROJECT_TEST_MGMT_ID, 'tuleap_testmgmt_template.xml');

        return $this;
    }

    /**
     * @return PFUser
     */
    private function generateUser() {
        $user = new PFUser();
        $user->setUserName(self::USER_TESTER_NAME);
        $user->setPassword(self::USER_TESTER_PASS);
        $user->setStatus(self::USER_TESTER_STATUS);
        $user->setLanguage($GLOBALS['Language']);
        $this->user_manager->createAccount($user);

        return $user;
    }

    public function generateProject() {
        $this->setGlobalsForProjectCreation();

        $user_test_rest_1 = $this->generateUser();

        echo "Create Trafficlights Project\n";

        $project = $this->createProject(
            self::PROJECT_TEST_MGMT_SHORTNAME,
            'Test-mgmt',
            true,
            array($user_test_rest_1),
            array($user_test_rest_1),
            array()
        );

        $this->unsetGlobalsForProjectCreation();

        return $project;
    }
}