<?php
/**
 * Copyright (c) Enalean, 2015-Present. All Rights Reserved.
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

declare(strict_types=1);

namespace Tuleap\REST;

use BaseLanguage;
use ProjectManager;
use UserManager;

class BaseTestDataBuilder
{
    final public const string ADMIN_USER_NAME         = 'admin';
    final public const string TEST_USER_1_NAME        = 'rest_api_tester_1';
    final public const string TEST_USER_1_REALNAME    = 'Test User 1';
    final public const string TEST_USER_1_DISPLAYNAME = 'Test User 1 (rest_api_tester_1)';
    final public const string TEST_USER_1_PASS        = 'welcome0';
    final public const string TEST_USER_1_EMAIL       = 'test_user_1@example.com';
    final public const string TEST_USER_1_LDAPID      = 'tester1';

    final public const string TEST_USER_2_NAME        = 'rest_api_tester_2';
    final public const string TEST_USER_2_DISPLAYNAME = ' (rest_api_tester_2)';
    final public const string TEST_USER_2_PASS        = 'welcome0';
    final public const string TEST_USER_2_EMAIL       = 'test_user_2@example.com';

    final public const string TEST_USER_3_NAME        = 'rest_api_tester_3';
    final public const string TEST_USER_3_DISPLAYNAME = ' (rest_api_tester_3)';
    final public const string TEST_USER_3_EMAIL       = 'test_user_3@example.com';

    final public const string TEST_USER_5_NAME        = 'rest_api_tester_5';
    final public const string TEST_USER_5_DISPLAYNAME = ' (rest_api_tester_5)';
    final public const string TEST_USER_5_PASS        = 'welcome0';
    final public const string TEST_USER_5_EMAIL       = 'test_user_5@example.com';

    final public const string TEST_USER_RESTRICTED_1_NAME        = 'rest_api_restricted_1';
    final public const string TEST_USER_RESTRICTED_1_DISPLAYNAME = ' (rest_api_restricted_1)';
    final public const string TEST_USER_RESTRICTED_1_EMAIL       = 'rest_api_restricted_1@example.com';

    final public const string TEST_USER_RESTRICTED_2_NAME        = 'rest_api_restricted_2';
    final public const string TEST_USER_RESTRICTED_2_DISPLAYNAME = ' (rest_api_restricted_2)';
    final public const string TEST_USER_RESTRICTED_2_EMAIL       = 'rest_api_restricted_2@example.com';

    final public const string TEST_USER_DELEGATED_REST_PROJECT_MANAGER_NAME = 'rest_api_delegated_rest_project_manager';
    final public const string TEST_USER_DELEGATED_REST_PROJECT_MANAGER_PASS = 'welcome0';

    final public const string TEST_USER_CATCH_ALL_PROJECT_ADMIN = 'rest_api_catch_all_project_admin';

    final public const int DEFAULT_TEMPLATE_PROJECT_ID = 100;

    final public const string PROJECT_PRIVATE_MEMBER_SHORTNAME         = 'private-member';
    final public const string PROJECT_PRIVATE_MEMBER_LABEL             = 'Private member';
    final public const string PROJECT_PRIVATE_SHORTNAME                = 'private';
    final public const string PROJECT_PUBLIC_SHORTNAME                 = 'public';
    final public const string PROJECT_PUBLIC_MEMBER_SHORTNAME          = 'public-member';
    final public const string PROJECT_PBI_SHORTNAME                    = 'pbi-6348';
    final public const string PROJECT_COMPUTED_FIELDS                  = 'computedfields';
    final public const string PROJECT_BURNDOWN                         = 'burndown-generation';
    final public const string PROJECT_DELETED_SHORTNAME                = 'deleted-project';
    final public const string PROJECT_SUSPENDED_SHORTNAME              = 'suspended-project';
    final public const string PROJECT_SERVICES                         = 'project-services';
    final public const string PROJECT_PUBLIC_WITH_MEMBERSHIP_SHORTNAME = 'public-sync-project-member';
    final public const string PROJECT_FUTURE_RELEASES                  = 'current-future-releases';
    final public const string PROJECT_PUBLIC_TEMPLATE                  = 'public-template';
    final public const string PROJECT_PRIVATE_TEMPLATE                 = 'private-template';

    final public const int STATIC_UGROUP_1_ID       = 101;
    final public const string STATIC_UGROUP_1_LABEL = 'static_ugroup_1';

    final public const int STATIC_UGROUP_2_ID       = 102;
    final public const string STATIC_UGROUP_2_LABEL = 'static_ugroup_2';

    final public const int STATIC_PRIVATE_MEMBER_UGROUP_DEVS_ID       = 103;
    final public const string STATIC_PRIVATE_MEMBER_UGROUP_DEVS_LABEL = 'developers';

    final public const int STATIC_PUBLIC_MEMBER_UGROUP_DEVS_ID = 104;

    final public const int STATIC_PUBLIC_INCL_RESTRICTED_UGROUP_DEVS_ID = 105;

    final public const string STATIC_PUBLIC_WITH_MEMBERSHIP_UGROUP_DEVS_LABEL = 'developers';

    final public const int DYNAMIC_UGROUP_PROJECT_MEMBERS_ID      = 3;
    final public const string DYNAMIC_UGROUP_PROJECT_MEMBERS_KEY  = 'ugroup_project_members_name_key';
    final public const int DYNAMIC_UGROUP_PROJECT_ADMINS_ID       = 4;
    final public const string DYNAMIC_UGROUP_PROJECT_ADMINS_LABEL = 'project_admins';
    final public const int DYNAMIC_UGROUP_FILE_MANAGER_ID         = 11;
    final public const string DYNAMIC_UGROUP_FILE_MANAGER_LABEL   = 'file_manager_admins';
    final public const int DYNAMIC_UGROUP_WIKI_ADMIN_ID           = 14;
    final public const int DYNAMIC_UGROUP_FORUM_ADMIN_ID          = 16;
    final public const int DYNAMIC_UGROUP_NEWS_ADMIN_ID           = 17;
    final public const int DYNAMIC_UGROUP_NEWS_WRITER_ID          = 18;

    protected readonly ProjectManager $project_manager;
    protected readonly UserManager $user_manager;

    public function __construct()
    {
        $this->project_manager = ProjectManager::instance();
        $this->user_manager    = UserManager::instance();

        $GLOBALS['Language'] = new BaseLanguage('en_US', 'en_US');
    }
}
