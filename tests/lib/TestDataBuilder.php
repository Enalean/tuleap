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

class TestDataBuilder {

    const ADMIN_ID             = 101;
    const ADMIN_USER_NAME      = 'admin';
    const ADMIN_USER_PASS      = 'siteadmin';
    const ADMIN_REAL_NAME      = 'Site Administrator';
    const ADMIN_DISPLAY_NAME   = 'Site Administrator (admin)';
    const ADMIN_EMAIL          = 'codendi-admin@_DOMAIN_NAME_';
    const ADMIN_STATUS         = 'A';

    const TEST_USER_1_ID          = 102;
    const TEST_USER_1_NAME        = 'rest_api_tester_1';
    const TEST_USER_1_REALNAME    = 'Test User 1';
    const TEST_USER_1_DISPLAYNAME = 'Test User 1 (rest_api_tester_1)';
    const TEST_USER_1_PASS        = 'welcome0';
    const TEST_USER_1_EMAIL       = 'test_user_1@myzupermail.com';
    const TEST_USER_1_LDAPID      = 'tester1';
    const TEST_USER_1_STATUS      = 'A';

    const TEST_USER_2_ID          = 103;
    const TEST_USER_2_NAME        = 'rest_api_tester_2';
    const TEST_USER_2_DISPLAYNAME = ' (rest_api_tester_2)';
    const TEST_USER_2_PASS        = 'welcome0';
    const TEST_USER_2_STATUS      = 'A';
    const TEST_USER_2_EMAIL       = 'test_user_2@myzupermail.com';

    const TEST_USER_3_ID          = 104;
    const TEST_USER_3_NAME        = 'rest_api_tester_3';
    const TEST_USER_3_DISPLAYNAME = ' (rest_api_tester_3)';
    const TEST_USER_3_PASS        = 'welcome0';
    const TEST_USER_3_STATUS      = 'A';
    const TEST_USER_3_EMAIL       = 'test_user_3@myzupermail.com';

    const ADMIN_PROJECT_ID          = 100;
    const PROJECT_PRIVATE_MEMBER_ID = 101;
    const PROJECT_PRIVATE_ID        = 102;
    const PROJECT_PUBLIC_ID         = 103;
    const PROJECT_PUBLIC_MEMBER_ID  = 104;
    const PROJECT_PBI_ID            = 105;

    const PROJECT_PRIVATE_MEMBER_SHORTNAME = 'private-member';
    const PROJECT_PRIVATE_SHORTNAME        = 'private';
    const PROJECT_PUBLIC_SHORTNAME         = 'public';
    const PROJECT_PUBLIC_MEMBER_SHORTNAME  = 'public-member';
    const PROJECT_PBI_SHORTNAME            = 'pbi-6348';
    const PROJECT_BACKLOG_DND              = 'dragndrop';

    const STATIC_UGROUP_1_ID    = 101;
    const STATIC_UGROUP_1_LABEL = 'static_ugroup_1';

    const STATIC_UGROUP_2_ID    = 102;
    const STATIC_UGROUP_2_LABEL = 'static_ugroup_2';

    const DYNAMIC_UGROUP_PROJECT_MEMBERS_ID        = 3;
    const DYNAMIC_UGROUP_PROJECT_MEMBERS_LABEL     = 'Project members';
    const DYNAMIC_UGROUP_PROJECT_MEMBERS_KEY       = 'ugroup_project_members_name_key';
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

    /** @var ProjectCreator */
    protected $project_creator;

    /** @var ProjectManager */
    protected $project_manager;

    /** @var UserManager */
    protected $user_manager;

    /** @var UserPermissionsDao */
    protected $user_permissions_dao;

    public function __construct() {
        $this->project_manager      = ProjectManager::instance();
        $this->user_manager         = UserManager::instance();
        $this->user_permissions_dao = new UserPermissionsDao();

        $this->project_creator = new ProjectCreator($this->project_manager);

        $GLOBALS['Language'] = new BaseLanguage('en_US', 'en_US');
        $GLOBALS['sys_lf'] = '\n';
    }

    public function activateDebug() {
	ForgeConfig::set('DEBUG_MODE', 1);
        return $this;
    }

    protected function activatePlugin($name) {
        $plugin_factory = PluginFactory::instance();
        $plugin = $plugin_factory->createPlugin($name);
        $plugin_factory->availablePlugin($plugin);
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

    /**
     * Instantiates a project with user, groups, admins ...
     *
     * @param string $project_short_name
     * @param string $project_long_name
     * @param string $is_public
     * @param array  $project_members
     * @param array  $project_admins
     */
    protected function createProject(
        $project_short_name,
        $project_long_name,
        $is_public,
        array $project_members,
        array $project_admins,
        array $services
    ) {

        $user = $this->user_manager->getUserByUserName(self::ADMIN_USER_NAME);
        $this->user_manager->setCurrentUser($user);

        $project = $this->project_creator->create($project_short_name, $project_long_name, array(
            'project' => array(
                'form_short_description' => '',
                'is_test'                => false,
                'is_public'              => $is_public,
                'services'               => $services,
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

    protected function addUserToUserGroup($user, $project, $ugroup_id) {
        ugroup_add_user_to_ugroup($project->getId(), $ugroup_id, $user->getId());
    }

    protected function addUserGroupsToProject(Project $project) {
        ugroup_create($project->getId(), 'static_ugroup_1', 'static_ugroup_1', '');
        ugroup_create($project->getId(), 'static_ugroup_2', 'static_ugroup_2', '');
    }
}
