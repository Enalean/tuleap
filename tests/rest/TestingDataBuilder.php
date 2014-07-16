<?php
/**
 * Copyright (c) Enalean, 2014. All rights reserved
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

use Tuleap\Testing\Config;
use Tuleap\Testing\Dao;

class TestingDataBuilder extends TestDataBuilder {

    const PROJECT_TEST_MGMT_ID        = 106;
    const PROJECT_TEST_MGMT_SHORTNAME = 'test-mgmt';

    const CAMPAIGN_TRACKER_ID         = 18;
    const TEST_EXEC_TRACKER_ID        = 19;
    const TEST_DEF_TRACKER_ID         = 20;

    public function setUp() {

        $this->installPlugin();
        $this->activatePlugin('testing');
        $project = $this->generateProject();
        $this->importTestingTemplate();
        $this->configureTestingPluginForProject($project);
    }

    private function configureTestingPluginForProject(Project $project) {
        $testing_config = new Config(new Dao());
        $testing_config->setProjectConfiguration($project, self::CAMPAIGN_TRACKER_ID, self::TEST_DEF_TRACKER_ID, self::TEST_EXEC_TRACKER_ID);
    }

    private function installPlugin() {
        $dbtables = new DBTablesDAO();
        $dbtables->updateFromFile(dirname(__FILE__).'/../../db/install.sql');
    }

    public function importTestingTemplate() {
        echo "Import Testing XML Template\n";

        $this->importTemplateInProject(self::PROJECT_TEST_MGMT_ID, 'tuleap_testmgmt_template.xml');

        return $this;
    }

    private function importTemplateInProject($project_id, $template) {
        $xml_importer = new ProjectXMLImporter(
            EventManager::instance(),
            $this->project_manager
        );
        $this->user_manager->forceLogin(self::ADMIN_USER_NAME);
        $xml_importer->import($project_id, dirname(__FILE__).'/_fixtures/'.$template);
    }

    public function generateProject() {
        $GLOBALS['svn_prefix'] = '/tmp';
        $GLOBALS['cvs_prefix'] = '/tmp';
        $GLOBALS['grpdir_prefix'] = '/tmp';
        $GLOBALS['ftp_frs_dir_prefix'] = '/tmp';
        $GLOBALS['ftp_anon_dir_prefix'] = '/tmp';

        $user_test_rest_1 = $this->user_manager->getUserByUserName(self::TEST_USER_1_NAME);

        echo "Create Testing Project\n";

        $project = $this->createProject(
            self::PROJECT_TEST_MGMT_SHORTNAME,
            'Test-mgmt',
            true,
            array($user_test_rest_1),
            array($user_test_rest_1)
        );

        unset($GLOBALS['svn_prefix']);
        unset($GLOBALS['cvs_prefix']);
        unset($GLOBALS['grpdir_prefix']);
        unset($GLOBALS['ftp_frs_dir_prefix']);
        unset($GLOBALS['ftp_anon_dir_prefix']);

        return $project;
    }
}