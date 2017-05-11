<?php
/**
 * Copyright (c) Enalean, 2015 - 2017. All rights reserved
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

class SOAP_TestDataBuilder extends TestDataBuilder {

    const TV3_SERVICE_ID      = 15;
    const TV3_TASK_REPORT_ID  = 102;

    const PROJECT_PRIVATE_MEMBER_ID = 101;

    public function __construct() {
        parent::__construct();
    }

    public function activatePlugins() {
        $this->activatePlugin('docman');
        PluginManager::instance()->invalidateCache();
        PluginManager::instance()->loadPlugins();

        return $this;
    }

    public function initPlugins() {
        foreach (glob(dirname(__FILE__).'/../../../plugins/*/tests/soap/init_test_data.php') as $init_file) {
            require_once $init_file;
        }
    }

    public function generateUsers() {
        $user_1 = new PFUser();
        $user_1->setUserName(self::TEST_USER_1_NAME);
        $user_1->setRealName(self::TEST_USER_1_REALNAME);
        $user_1->setLdapId(self::TEST_USER_1_LDAPID);
        $user_1->setPassword(self::TEST_USER_1_PASS);
        $user_1->setStatus(self::TEST_USER_1_STATUS);
        $user_1->setEmail(self::TEST_USER_1_EMAIL);
        $user_1->setLanguage($GLOBALS['Language']);
        $this->user_manager->createAccount($user_1);
        $user_1->setLabFeatures(true);

        $user_2 = new PFUser();
        $user_2->setUserName(self::TEST_USER_2_NAME);
        $user_2->setPassword(self::TEST_USER_2_PASS);
        $user_2->setStatus(self::TEST_USER_2_STATUS);
        $user_2->setEmail(self::TEST_USER_2_EMAIL);
        $user_2->setLanguage($GLOBALS['Language']);
        $this->user_manager->createAccount($user_2);
        $user_2->setLabFeatures(true);

        return $this;
    }

    public function generateProject() {
        $this->setGlobalsForProjectCreation();

        $user_test_soap = $this->user_manager->getUserByUserName(self::TEST_USER_1_NAME);

        echo "Create projects\n";

        $services = array(
            self::TV3_SERVICE_ID => array('is_used' => '1')
        );

        $project_1 = $this->createProject(
            self::PROJECT_PRIVATE_MEMBER_SHORTNAME,
            'Private member',
            false,
            array($user_test_soap),
            array($user_test_soap),
            $services
        );
        $this->addUserGroupsToProject($project_1);

        $this->unsetGlobalsForProjectCreation();

        return $this;
    }
}
