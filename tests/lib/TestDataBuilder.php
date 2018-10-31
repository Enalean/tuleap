<?php
/**
 * Copyright (c) Enalean, 2015 - 2018. All rights reserved
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

class TestDataBuilder
{
    const ADMIN_USER_NAME      = 'admin';
    const ADMIN_REAL_NAME      = 'Site Administrator';
    const ADMIN_DISPLAY_NAME   = 'Site Administrator (admin)';
    const ADMIN_EMAIL          = 'codendi-admin@_DOMAIN_NAME_';
    const ADMIN_STATUS         = 'A';
    const ADMIN_PASSWORD       = 'welcome0';

    const TEST_USER_1_NAME        = 'rest_api_tester_1';
    const TEST_USER_1_REALNAME    = 'Test User 1';
    const TEST_USER_1_DISPLAYNAME = 'Test User 1 (rest_api_tester_1)';
    const TEST_USER_1_PASS        = 'welcome0';
    const TEST_USER_1_EMAIL       = 'test_user_1@example.com';
    const TEST_USER_1_LDAPID      = 'tester1';
    const TEST_USER_1_STATUS      = 'A';

    const TEST_USER_2_NAME        = 'rest_api_tester_2';
    const TEST_USER_2_DISPLAYNAME = ' (rest_api_tester_2)';
    const TEST_USER_2_PASS        = 'welcome0';
    const TEST_USER_2_STATUS      = 'A';
    const TEST_USER_2_EMAIL       = 'test_user_2@example.com';

    const TEST_USER_3_NAME        = 'rest_api_tester_3';
    const TEST_USER_3_DISPLAYNAME = ' (rest_api_tester_3)';
    const TEST_USER_3_PASS        = 'welcome0';
    const TEST_USER_3_STATUS      = 'A';
    const TEST_USER_3_EMAIL       = 'test_user_3@example.com';

    const TEST_USER_5_NAME        = 'rest_api_tester_5';
    const TEST_USER_5_DISPLAYNAME = ' (rest_api_tester_5)';
    const TEST_USER_5_PASS        = 'welcome0';
    const TEST_USER_5_STATUS      = 'A';
    const TEST_USER_5_EMAIL       = 'test_user_5@example.com';

    const TEST_USER_RESTRICTED_1_NAME        = 'rest_api_restricted_1';
    const TEST_USER_RESTRICTED_1_DISPLAYNAME = ' (rest_api_restricted_1)';
    const TEST_USER_RESTRICTED_1_PASS        = 'welcome0';
    const TEST_USER_RESTRICTED_1_STATUS      = 'R';
    const TEST_USER_RESTRICTED_1_EMAIL       = 'rest_api_restricted_1@example.com';

    const TEST_USER_RESTRICTED_2_NAME        = 'rest_api_restricted_2';
    const TEST_USER_RESTRICTED_2_DISPLAYNAME = ' (rest_api_restricted_2)';
    const TEST_USER_RESTRICTED_2_PASS        = 'welcome0';
    const TEST_USER_RESTRICTED_2_STATUS      = 'R';
    const TEST_USER_RESTRICTED_2_EMAIL       = 'rest_api_restricted_2@example.com';

    const TEST_USER_DELEGATED_REST_PROJECT_MANAGER_NAME        = "rest_api_delegated_rest_project_manager";
    const TEST_USER_DELEGATED_REST_PROJECT_MANAGER_DISPLAYNAME = ' (rest_api_delegated_rest_project_manager)';
    const TEST_USER_DELEGATED_REST_PROJECT_MANAGER_PASS        = 'welcome0';
    const TEST_USER_DELEGATED_REST_PROJECT_MANAGER_STATUS      = 'A';
    const TEST_USER_DELEGATED_REST_PROJECT_MANAGER_EMAIL       = 'rest_api_delegated_rest_project_manager@example.com';

    const ADMIN_PROJECT_ID = 100;

    const PROJECT_PRIVATE_MEMBER_SHORTNAME = 'private-member';
    const PROJECT_PRIVATE_MEMBER_LABEL     = 'Private member';
    const PROJECT_PRIVATE_SHORTNAME        = 'private';
    const PROJECT_PUBLIC_SHORTNAME         = 'public';
    const PROJECT_PUBLIC_MEMBER_SHORTNAME  = 'public-member';
    const PROJECT_PBI_SHORTNAME            = 'pbi-6348';
    const PROJECT_BACKLOG_DND              = 'dragndrop';
    const PROJECT_COMPUTED_FIELDS          = 'computedfields';
    const PROJECT_BURNDOWN                 = 'burndown-generation';
    const PROJECT_DELETED_SHORTNAME        = 'deleted-project';
    const PROJECT_SUSPENDED_SHORTNAME      = 'suspended-project';

    const STATIC_UGROUP_1_ID    = 101;
    const STATIC_UGROUP_1_LABEL = 'static_ugroup_1';

    const STATIC_UGROUP_2_ID    = 102;
    const STATIC_UGROUP_2_LABEL = 'static_ugroup_2';

    const STATIC_PRIVATE_MEMBER_UGROUP_DEVS_ID    = 103;
    const STATIC_PRIVATE_MEMBER_UGROUP_DEVS_LABEL = 'developers';

    const STATIC_PUBLIC_MEMBER_UGROUP_DEVS_ID    = 104;

    const STATIC_PUBLIC_INCL_RESTRICTED_UGROUP_DEVS_ID    = 105;

    const DYNAMIC_UGROUP_PROJECT_MEMBERS_ID        = 3;
    const DYNAMIC_UGROUP_PROJECT_MEMBERS_KEY       = 'ugroup_project_members_name_key';
    const DYNAMIC_UGROUP_PROJECT_ADMINS_ID         = 4;
    const DYNAMIC_UGROUP_PROJECT_ADMINS_LABEL      = 'project_admins';
    const DYNAMIC_UGROUP_AUTHENTICATED_USERS_ID    = 5;
    const DYNAMIC_UGROUP_AUTHENTICATED_USERS_LABEL = 'authenticated_users';
    const DYNAMIC_UGROUP_FILE_MANAGER_ID           = 11;
    const DYNAMIC_UGROUP_FILE_MANAGER_LABEL        = 'file_manager_admins';
    const DYNAMIC_UGROUP_WIKI_ADMIN_ID             = 14;
    const DYNAMIC_UGROUP_FORUM_ADMIN_ID            = 16;
    const DYNAMIC_UGROUP_NEWS_ADMIN_ID             = 17;
    const DYNAMIC_UGROUP_NEWS_WRITER_ID            = 18;

    /** @var ProjectManager */
    protected $project_manager;

    /** @var UserManager */
    protected $user_manager;

    public function __construct()
    {
        $this->project_manager = ProjectManager::instance();
        $this->user_manager    = UserManager::instance();

        $GLOBALS['Language'] = new BaseLanguage('en_US', 'en_US');
        $GLOBALS['sys_lf'] = '\n';
    }

    public function activateDebug()
    {
        ForgeConfig::set('DEBUG_MODE', 1);
        return $this;
    }

    protected function activatePlugin($name)
    {
        $plugin_factory = PluginFactory::instance();
        $plugin = $plugin_factory->createPlugin($name);
        $plugin_factory->availablePlugin($plugin);
    }
}
