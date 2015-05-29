<?php
/**
 * Copyright (c) Enalean, 2015. All rights reserved
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

class SOAP_TestDataBuilder {

    const ADMIN_ID         = 101;
    const ADMIN_USER_NAME  = 'admin';
    const ADMIN_USER_PASS  = 'siteadmin';
    const ADMIN_REAL_NAME      = 'Site Administrator';
    const ADMIN_EMAIL          = 'codendi-admin@_DOMAIN_NAME_';
    const ADMIN_STATUS         = 'A';

    const TEST_USER_1_ID       = 102;
    const TEST_USER_1_NAME     = 'rest_api_tester_1';
    const TEST_USER_1_REALNAME = 'Test User 1';
    const TEST_USER_1_PASS     = 'welcome0';
    const TEST_USER_1_EMAIL    = 'test_user_1@myzupermail.com';
    const TEST_USER_1_LDAPID   = 'tester1';
    const TEST_USER_1_STATUS   = 'A';

    const ADMIN_PROJECT_ID          = 100;
    const PROJECT_PRIVATE_MEMBER_ID = 101;

    const PROJECT_PRIVATE_MEMBER_SHORTNAME = 'private-member';

    const STATIC_UGROUP_1_ID    = 101;
    const STATIC_UGROUP_1_LABEL = 'static_ugroup_1';
    const STATIC_UGROUP_2_ID    = 102;
    const STATIC_UGROUP_2_LABEL = 'static_ugroup_2';

    const DYNAMIC_UGROUP_PROJECT_MEMBERS_ID        = 3;
    const DYNAMIC_UGROUP_PROJECT_MEMBERS_LABEL     = 'project_members';
    const DYNAMIC_UGROUP_PROJECT_ADMINS_ID         = 4;
    const DYNAMIC_UGROUP_PROJECT_ADMINS_LABEL      = 'project_admins';
    const DYNAMIC_UGROUP_AUTHENTICATED_USERS_ID    = 5;
    const DYNAMIC_UGROUP_AUTHENTICATED_USERS_LABEL = 'authenticated_users';
    const DYNAMIC_UGROUP_FILE_MANAGER_ID           = 11;
    const DYNAMIC_UGROUP_FILE_MANAGER_LABEL        = 'file_manager_admins';
    const DYNAMIC_UGROUP_DOCUMENT_TECH_ID          = 12;
    const DYNAMIC_UGROUP_DOCUMENT_TECH_LABEL       = 'document_techs';
    const DYNAMIC_UGROUP_DOCUMENT_ADMIN_ID         = 13;
    const DYNAMIC_UGROUP_DOCUMENT_ADMIN_LABEL      = 'document_admins';
    const DYNAMIC_UGROUP_WIKI_ADMIN_ID             = 14;
    const DYNAMIC_UGROUP_WIKI_ADMIN_LABEL          = 'wiki_admins';

    const TV3_SERVICE_ID = 15;

    /** @var ProjectManager */
    protected $project_manager;

    /** @var UserManager */
    protected $user_manager;

    /** @var ProjectCreator */
    private $project_creator;

    /** @var UserPermissionsDao */
    private $user_permissions_dao;

    public function __construct() {
        $this->project_manager      = ProjectManager::instance();
        $this->user_manager         = UserManager::instance();
        $this->user_permissions_dao = new UserPermissionsDao();

        $this->project_creator = new ProjectCreator(
            $this->project_manager,
            new Rule_ProjectName(),
            new Rule_ProjectFullName()
        );

        $GLOBALS['Language'] = new BaseLanguage('en_US', 'en_US');
        $GLOBALS['sys_lf'] = '\n';
    }

    public function activateDebug() {
	ForgeConfig::set('DEBUG_MODE', 1);
        return $this;
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

        return $this;
    }

    protected function setGlobalsForProjectCreation() {
        $GLOBALS['svn_prefix'] = '/tmp';
        $GLOBALS['cvs_prefix'] = '/tmp';
        $GLOBALS['grpdir_prefix'] = '/tmp';
        $GLOBALS['ftp_frs_dir_prefix'] = '/tmp';
        $GLOBALS['ftp_anon_dir_prefix'] = '/tmp';
    }

    protected function unsetGlobalsForProjectCreation() {
        unset($GLOBALS['svn_prefix']);
        unset($GLOBALS['cvs_prefix']);
        unset($GLOBALS['grpdir_prefix']);
        unset($GLOBALS['ftp_frs_dir_prefix']);
        unset($GLOBALS['ftp_anon_dir_prefix']);
    }

    public function generateProject() {
        $this->setGlobalsForProjectCreation();

        $user_test_soap = $this->user_manager->getUserByUserName(self::TEST_USER_1_NAME);

        echo "Create projects\n";

        $project_1 = $this->createProject(
            self::PROJECT_PRIVATE_MEMBER_SHORTNAME,
            'Private member',
            false,
            array($user_test_soap),
            array($user_test_soap)
        );
        $this->addUserGroupsToProject($project_1);

        $this->unsetGlobalsForProjectCreation();

        return $this;
    }

    /**
     * Instantiates a project with user, groups, admins ...
     *
     * @param string $project_short_name
     * @param string $project_long_name
     * @param string $is_public
     * @param array  $project_members
     * @param array  $project_admins
     *
     * @return Project
     */
    protected function createProject(
        $project_short_name,
        $project_long_name,
        $is_public,
        array $project_members,
        array $project_admins
    ) {

        $user = $this->user_manager->getUserByUserName(self::ADMIN_USER_NAME);
        $this->user_manager->setCurrentUser($user);

        $project = $this->project_creator->create($project_short_name, $project_long_name, array(
            'project' => array(
                'form_license'           => 'xrx',
                'form_license_other'     => '',
                'form_short_description' => '',
                'is_test'                => false,
                'is_public'              => $is_public,
                'services'               => array(
                    self::TV3_SERVICE_ID => array('is_used' => '1')
                ),
                'built_from_template'    => 100,
            )
        ));

        $this->project_manager->activate($project);

        foreach ($project_members as $project_member) {
            $this->addMembersToProject($project, $project_member);
        }

        foreach ($project_admins as $project_admin) {
            $this->addAdminToProject($project, $project_admin);
        }

        return $project;
    }

    private function addMembersToProject(Project $project, PFUser $user) {
        $GLOBALS['sys_email_admin'] = 'noreply@localhost';
        account_add_user_to_group($project->getId(), $user->getUnixName());
        unset($GLOBALS['sys_email_admin']);
        UserManager::clearInstance();
        $this->user_manager = UserManager::instance();
    }

    private function addAdminToProject(Project $project, PFUser $user) {
       $this->user_permissions_dao->addUserAsProjectAdmin($project, $user);
    }

    private function addUserGroupsToProject(Project $project) {
        ugroup_create($project->getId(), 'static_ugroup_1', 'static_ugroup_1', '');
        ugroup_create($project->getId(), 'static_ugroup_2', 'static_ugroup_2', '');
    }
}
