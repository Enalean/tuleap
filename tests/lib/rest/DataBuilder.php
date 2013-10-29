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
date_default_timezone_set('Europe/London');

require_once 'common/autoload.php';
require_once dirname(__FILE__).'/../autoload.php';

class DataBuilder {

    const ADMIN_USER_NAME  = 'admin';
    const ADMIN_USER_PASS  = 'siteadmin';
    const TEST_USER_NAME   = 'rest_api_tester';
    const TEST_USER_PASS   = 'welcome0';
    const ADMIN_PROJECT_ID = 100;

    /** @var DBTestAccess */
    private $db;

    public function __construct() {
        $this->db = new DBTestAccess();
    }

    public function setUpDatabase() {
        $this->db->setUp();
        return $this;
    }

    public function activateDebug() {
	Config::set('DEBUG_MODE', true);
        return $this;
    }

    public function generateUser() {
        $language = new BaseLanguage('en_US', 'en_US');
        $GLOBALS['Language'] = $language;

        $user = new PFUser();
        $user->setUserName('rest_api_tester');
        $user->setPassword('welcome0');
        $user->setLanguage($language);

        $account_manager = UserManager::instance();
        $account_manager->createAccount($user);

        return $this;
    }
}
?>