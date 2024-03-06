<?php
/**
 * Copyright (c) Enalean, 2017 - Present. All Rights Reserved.
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
 *
 */

use Tuleap\admin\ProjectCreation\ProjectFields\ProjectFieldsDao;
use Tuleap\admin\ProjectEdit\ProjectEditDao;
use Tuleap\Project\Admin\ProjectDetails\ProjectDetailsDAO;
use Tuleap\User\ForgeUserGroupPermission\RestProjectManagementPermission;
use Tuleap\User\ForgeUserGroupPermission\RESTReadOnlyAdmin\RestReadOnlyAdminPermission;

require_once __DIR__ . '/../../lib/TestDataBuilder.php';

class REST_TestDataBuilder extends TestDataBuilder  // @codingStandardsIgnoreLine
{
    public const STANDARD_PASSWORD = 'welcome0';

    public const TEST_USER_4_NAME = 'rest_api_tester_4';

    public const TEST_BOT_USER_NAME   = 'rest_bot_read_only_admin';
    public const TEST_BOT_USER_PASS   = 'welcome0';
    public const TEST_BOT_USER_STATUS = 'A';
    public const TEST_BOT_USER_MAIL   = 'test_bot_user@example.com';

    public const EPICS_TRACKER_SHORTNAME        = 'epic';
    public const RELEASES_TRACKER_SHORTNAME     = 'rel';
    public const SPRINTS_TRACKER_SHORTNAME      = 'sprint';
    public const TASKS_TRACKER_SHORTNAME        = 'task';
    public const USER_STORIES_TRACKER_SHORTNAME = 'story';
    public const DELETED_TRACKER_SHORTNAME      = 'delete';
    public const KANBAN_TRACKER_SHORTNAME       = 'kanbantask';

    public const LEVEL_ONE_TRACKER_SHORTNAME   = 'LevelOne';
    public const LEVEL_TWO_TRACKER_SHORTNAME   = 'LevelTwo';
    public const LEVEL_THREE_TRACKER_SHORTNAME = 'LevelThree';
    public const LEVEL_FOUR_TRACKER_SHORTNAME  = 'LevelFour';

    public const EPICS_TRACKER_LABEL         = 'Epics';
    public const KANBAN_TRACKER_LABEL        = 'Kanban Tasks';
    public const SUSPENDED_TRACKER_SHORTNAME = 'suspended_tracker';

    public const NIVEAU_1_TRACKER_SHORTNAME = 'niveau1';
    public const NIVEAU_2_TRACKER_SHORTNAME = 'niveau2';
    public const POKEMON_TRACKER_SHORTNAME  = 'pokemon';

    public const KANBAN_ID = 1;

    public const KANBAN_TO_BE_DONE_COLUMN_ID  = 230;
    public const KANBAN_ONGOING_COLUMN_ID     = 231;
    public const KANBAN_REVIEW_COLUMN_ID      = 232;
    public const KANBAN_OTHER_VALUE_COLUMN_ID = 233;

    public const PLANNING_ID = 2;

    public const PHPWIKI_PAGE_ID       = 6097;
    public const PHPWIKI_SPACE_PAGE_ID = 6100;

    /** @var TrackerFactory */
    protected $tracker_factory;

    /** @var string */
    protected $template_path;

    protected $release;
    protected $sprint;

    public function __construct()
    {
        parent::__construct();

        $this->template_path = __DIR__ . '/../../rest/_fixtures/';
    }

    public function instanciateFactories()
    {
        $this->tracker_factory = TrackerFactory::instance();

        return $this;
    }

    public function initPlugins()
    {
        foreach (glob(__DIR__ . '/../../../plugins/*/tests/rest/init_test_data.php') as $init_file) {
            echo "Init data from $init_file\n";
            (new \Symfony\Component\Process\Process([__DIR__ . '/../../../src/utils/php-launcher.sh', $init_file]))->mustRun();
        }
    }

    public function generateUsers()
    {
        $this->initPassword(self::ADMIN_USER_NAME, self::STANDARD_PASSWORD);
        $this->initPassword(self::TEST_USER_1_NAME, self::STANDARD_PASSWORD);
        $this->initPassword(self::TEST_USER_3_NAME, self::STANDARD_PASSWORD);
        $this->initPassword(self::TEST_USER_4_NAME, self::STANDARD_PASSWORD);
        $this->initPassword(self::TEST_USER_CATCH_ALL_PROJECT_ADMIN, self::STANDARD_PASSWORD);

        $user_2 = $this->user_manager->getUserByUserName(self::TEST_USER_2_NAME);
        $user_2->setPassword(new \Tuleap\Cryptography\ConcealedString(self::TEST_USER_2_PASS));
        $user_2->setAuthorizedKeys('ssh-rsa AAAAB3NzaC1yc2EAAAADAQABAAABAQDHk9 toto@marche');
        $this->user_manager->updateDb($user_2);

        $user_5 = $this->user_manager->getUserByUserName(self::TEST_USER_5_NAME);
        $user_5->setPassword(new \Tuleap\Cryptography\ConcealedString(self::TEST_USER_5_PASS));
        $this->user_manager->updateDb($user_5);

        $delegated_rest_project_manager = $this->user_manager->getUserByUserName(self::TEST_USER_DELEGATED_REST_PROJECT_MANAGER_NAME);
        $delegated_rest_project_manager->setPassword(new \Tuleap\Cryptography\ConcealedString(self::TEST_USER_DELEGATED_REST_PROJECT_MANAGER_PASS));
        $this->user_manager->updateDb($delegated_rest_project_manager);

        $bot_rest_read_only_admin = new PFUser();
        $bot_rest_read_only_admin->setUserName(self::TEST_BOT_USER_NAME);
        $bot_rest_read_only_admin->setPassword(new \Tuleap\Cryptography\ConcealedString(self::TEST_BOT_USER_PASS));
        $bot_rest_read_only_admin->setStatus(self::TEST_BOT_USER_STATUS);
        $bot_rest_read_only_admin->setEmail(self::TEST_BOT_USER_MAIL);
        $bot_rest_read_only_admin->setLanguage($GLOBALS['Language']);
        $this->user_manager->createAccount($bot_rest_read_only_admin);

        return $this;
    }

    protected function initPassword(string $username, string $password): void
    {
        $user = $this->user_manager->getUserByUserName($username);
        $user->setPassword(new \Tuleap\Cryptography\ConcealedString($password));
        $this->user_manager->updateDb($user);
    }

    public function delegateForgePermissions()
    {
        $forge_permission_delegate = $this->user_manager->getUserByUserName(self::TEST_USER_3_NAME);

        $retrieve_membership_permission = new User_ForgeUserGroupPermission_RetrieveUserMembershipInformation();

        $manage_users_permission = new User_ForgeUserGroupPermission_UserManagement();
        $this->delegatePermissionToUser(
            $forge_permission_delegate,
            $manage_users_permission,
            'site remote admins'
        );

        $rest_project_management_delegate = $user = $this->user_manager->getUserByUserName(
            self::TEST_USER_DELEGATED_REST_PROJECT_MANAGER_NAME
        );

        $manage_project_through_rest_permission = new RestProjectManagementPermission();
        $this->delegatePermissionToUser(
            $rest_project_management_delegate,
            $manage_project_through_rest_permission,
            'REST projects managers'
        );

        $rest_read_only_bot_user         = $this->user_manager->getUserByUserName(self::TEST_BOT_USER_NAME);
        $rest_read_only_admin_permission = new RestReadOnlyAdminPermission();
        $this->delegatePermissionToUser(
            $rest_read_only_bot_user,
            $rest_read_only_admin_permission,
            'REST read only administrators'
        );

        return $this;
    }

    private function delegatePermissionToUser($user, $forge_ugroup_permission, $forge_ugroup_name)
    {
        // Create group
        $user_group_dao     = new UserGroupDao();
        $user_group_factory = new User_ForgeUserGroupFactory($user_group_dao);
        $user_group         = $user_group_factory->createForgeUGroup($forge_ugroup_name, '');

        // Grant Retrieve Membership permissions
        $permissions_dao                = new User_ForgeUserGroupPermissionsDao();
        $user_group_permissions_manager = new User_ForgeUserGroupPermissionsManager($permissions_dao);
        $user_group_permissions_manager->addPermission($user_group, $forge_ugroup_permission);

        // Add user to group
        $user_group_users_dao     = new User_ForgeUserGroupUsersDao();
        $user_group_users_manager = new User_ForgeUserGroupUsersManager($user_group_users_dao);
        $user_group_users_manager->addUserToForgeUserGroup($user, $user_group);
    }

    public function deleteTracker()
    {
        echo "Delete tracker\n";

        $tracker = $this->getDeletedTracker();

        $this->tracker_factory->markAsDeleted($tracker->getId());

        return $this;
    }

    public function deleteProject()
    {
        echo "Delete deleted-project";

        $project_manager = ProjectManager::instance();

        $project = $project_manager->getProjectByUnixName("deleted-project");
        $project_manager->updateStatus($project, PROJECT::STATUS_DELETED);

        return $this;
    }

    public function markProjectsAsTemplate()
    {
        echo "Mark public-template and private-template as template";

        $project_manager  = ProjectManager::instance();
        $public_template  = $project_manager->getProjectByUnixName("public-template");
        $private_template = $project_manager->getProjectByUnixName("private-template");

        $project_edit_dao = new ProjectEditDao();
        if ($public_template) {
            $project_edit_dao->updateProjectStatusAndType("A", TemplateSingleton::TEMPLATE, $public_template->getID());
        }

        if ($private_template) {
            $project_edit_dao->updateProjectStatusAndType("A", TemplateSingleton::TEMPLATE, $private_template->getID());
        }

        return $this;
    }

    public function createProjectField()
    {
        $project_fields_dao  = new ProjectFieldsDao();
        $project_details_dao = new ProjectDetailsDAO();
        $project_fields_dao->createProjectField("Test Rest", "Field for test rest", 2, "text", 0);
        $project_details_dao->createGroupDescription(self::DEFAULT_TEMPLATE_PROJECT_ID, 1, "Admin test");
    }

    public function suspendProject()
    {
        echo "Suspend supended-project";

        $project_manager = ProjectManager::instance();

        $project = $project_manager->getProjectByUnixName("suspended-project");
        $project_manager->updateStatus($project, PROJECT::STATUS_SUSPENDED);

        return $this;
    }

    /**
     * @return Tracker
     */
    private function getDeletedTracker()
    {
        return $this->getTrackerInProjectPrivateMember(self::DELETED_TRACKER_SHORTNAME);
    }

    protected function getTrackerInProjectPrivateMember($tracker_shortname)
    {
        return $this->getTrackerInProject($tracker_shortname, self::PROJECT_PRIVATE_MEMBER_SHORTNAME);
    }

    protected function getTrackerInProject($tracker_shortname, $project_shortname)
    {
        $project    = $this->project_manager->getProjectByUnixName($project_shortname);
        $project_id = $project->getID();

        foreach ($this->tracker_factory->getTrackersByGroupId($project_id) as $tracker) {
            if ($tracker->getItemName() === $tracker_shortname) {
                return $tracker;
            }
        }

        throw new RuntimeException('Data seems not correctly initialized');
    }
}
