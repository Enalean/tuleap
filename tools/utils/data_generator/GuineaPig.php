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

require_once 'account.php';
require_once 'www/project/admin/UserPermissionsDao.class.php';
require_once 'GuineaPigContent.php';
require_once 'DataInit/ProjectHelper.php';

class GuineaPig {

    const ADMIN_ID         = 101;
    const ADMIN_USER_NAME  = 'admin';
    const ADMIN_USER_PASS  = 'siteadmin';

    const ADMIN_REAL_NAME  = 'Site Administrator';
    const ADMIN_EMAIL      = 'codendi-admin@_DOMAIN_NAME_';

    const USER_RICHARD_NAME = 'richard_cover';
    const USER_RICHARD_PASS = 'welcome0';

    const USER_ALICE_NAME = 'alice_tyrell';
    const USER_ALICE_PASS = 'welcome0';

    const PROJECT_SHORT_NAME = 'gpig';
    const PROJECT_NAME       = 'Guinea Pig';

    /** @var ProjectManager */
    private $project_manager;

    /** @var UserManager */
    private $user_manager;

    /** @var DataInit_ProjectHelper */
    private $project_helper;

    private $richard;
    private $alice;
    private $guinea_pig;

    public function __construct() {
        $this->project_manager             = ProjectManager::instance();
        $this->user_manager                = UserManager::instance();

        $this->project_helper = new \DataInit\ProjectHelper();

        $GLOBALS['Language'] = new BaseLanguage('en_US', 'en_US');
    }

    public function setUp() {
        $this->generateUsers()
             ->generateProject()
             ->restSetUp();
    }

    public function generateUsers() {
        echo "Create users\n";

        $user = new PFUser();
        $user->setUserName(self::USER_RICHARD_NAME);
        $user->setPassword(self::USER_RICHARD_PASS);
        $user->setEmail(self::USER_RICHARD_NAME.'@localhost.localdomain');
        $user->setRealName("Richard Cover");
        $user->setLanguage($GLOBALS['Language']);
        $this->richard = UserManager::instance()->createAccount($user);

        $user = new PFUser();
        $user->setUserName(self::USER_ALICE_NAME);
        $user->setPassword(self::USER_ALICE_PASS);
        $user->setEmail(self::USER_ALICE_NAME.'@localhost.localdomain');
        $user->setRealName("Alice Tyrell");
        $user->setLanguage($GLOBALS['Language']);

        $this->alice = UserManager::instance()->createAccount($user);

        return $this;
    }

    public function generateProject() {
        echo "Create projects\n";

        $this->guinea_pig = $this->project_helper->createProject(
            self::PROJECT_SHORT_NAME,
            self::PROJECT_NAME,
            true,
            array($this->alice),
            array($this->richard)
        );

        $this->project_helper->importTemplateInProject(
            $this->guinea_pig,
            $this->richard,
            dirname(__FILE__).'/tuleap_agiledashboard_template.xml'
        );

        return $this;
    }

    private function restSetUp() {
        if (is_file('/usr/share/php/Guzzle/autoload.php')) {
            include_once '/usr/share/php/Guzzle/autoload.php';
        } elseif (is_file(dirname(__FILE__).'/../../../tests/lib/autoload.php')) {
            include_once dirname(__FILE__).'/../../../tests/lib/autoload.php';
        } else {
            var_dump("Guzzle not found, skip REST load\n");
            return $this;
        }
        $content = new GuineaPigContent(
            self::PROJECT_NAME,
            self::USER_RICHARD_NAME,
            self::USER_RICHARD_PASS
        );
        var_dump("Create Agile Dashboard Content");
        $content->setUp();
    }
}
