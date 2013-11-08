<?php
/**
 * Copyright (c) Enalean, 2013. All rights reserved
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

class TestDataBuilder {

    const ADMIN_USER_NAME  = 'admin';
    const ADMIN_USER_PASS  = 'siteadmin';
    const TEST_USER_NAME   = 'rest_api_tester';
    const TEST_USER_PASS   = 'welcome0';
    const ADMIN_PROJECT_ID = 100;

    /** @var ProjectManager */
    private $project_manager;

    /** @var UserManager */
    private $user_manager;

    public function __construct() {
        $this->project_manager = ProjectManager::instance();
        $this->user_manager    = UserManager::instance();
        $GLOBALS['Language']   = new BaseLanguage('en_US', 'en_US');
    }

    public function activateDebug() {
	Config::set('DEBUG_MODE', true);
        return $this;
    }

    public function generateUser() {
        $user = new PFUser();
        $user->setUserName(self::TEST_USER_NAME);
        $user->setPassword(self::TEST_USER_PASS);
        $user->setLanguage($GLOBALS['Language']);

        $this->user_manager->createAccount($user);

        return $this;
    }

    public function generateProject() {
        $GLOBALS['svn_prefix'] = '/tmp';
        $GLOBALS['cvs_prefix'] = '/tmp';
        $GLOBALS['grpdir_prefix'] = '/tmp';
        $GLOBALS['ftp_frs_dir_prefix'] = '/tmp';
        $GLOBALS['ftp_anon_dir_prefix'] = '/tmp';
        $GLOBALS['sys_default_domain'] = '';
        $GLOBALS['sys_cookie_prefix'] = '';
        $GLOBALS['sys_force_ssl'] = 0;

        $user = $this->user_manager->getUserByUserName(self::TEST_USER_NAME);
        $this->user_manager->setCurrentUser($user);

        $projectCreator = new ProjectCreator($this->project_manager, new Rule_ProjectName(), new Rule_ProjectFullName());
        $projectCreator->create('short-name', 'Long name', array(
            'project' => array(
                'form_license'           => 'xrx',
                'form_license_other'     => '',
                'form_short_description' => '',
                'is_test'                => false,
                'is_public'              => false,
                'services'               => array(),
                'built_from_template'    => 100,
            )
        ));

        unset($GLOBALS['svn_prefix']);
        unset($GLOBALS['cvs_prefix']);
        unset($GLOBALS['grpdir_prefix']);
        unset($GLOBALS['ftp_frs_dir_prefix']);
        unset($GLOBALS['ftp_anon_dir_prefix']);
        unset($GLOBALS['sys_default_domain']);
        unset($GLOBALS['sys_cookie_prefix']);
        unset($GLOBALS['sys_force_ssl']);

        return $this;
    }
}
?>