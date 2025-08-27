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

declare(strict_types=1);

namespace Tuleap\REST;

use PFUser;
use ProjectManager;
use TemplateSingleton;
use TrackerFactory;
use Tuleap\admin\ProjectCreation\ProjectFields\ProjectFieldsDao;
use Tuleap\admin\ProjectEdit\ProjectEditDao;
use Tuleap\Cryptography\ConcealedString;
use Tuleap\Project\Admin\ProjectDetails\ProjectDetailsDAO;
use Tuleap\Tracker\Tracker;
use Tuleap\User\ForgeUserGroupPermission\RestProjectManagementPermission;
use Tuleap\User\ForgeUserGroupPermission\RESTReadOnlyAdmin\RestReadOnlyAdminPermission;
use User_ForgeUserGroupFactory;
use User_ForgeUserGroupPermission_UserManagement;
use User_ForgeUserGroupPermissionsDao;
use User_ForgeUserGroupPermissionsManager;
use User_ForgeUserGroupUsersDao;
use User_ForgeUserGroupUsersManager;
use UserGroupDao;

class RESTTestDataBuilder extends BaseTestDataBuilder
{
    final public const string STANDARD_PASSWORD = 'welcome0';

    final public const string TEST_USER_4_NAME = 'rest_api_tester_4';

    final public const string TEST_BOT_USER_NAME   = 'rest_bot_read_only_admin';
    final public const string TEST_BOT_USER_PASS   = 'welcome0';
    final public const string TEST_BOT_USER_STATUS = 'A';
    final public const string TEST_BOT_USER_MAIL   = 'test_bot_user@example.com';

    final public const string EPICS_TRACKER_SHORTNAME        = 'epic';
    final public const string RELEASES_TRACKER_SHORTNAME     = 'rel';
    final public const string SPRINTS_TRACKER_SHORTNAME      = 'sprint';
    final public const string TASKS_TRACKER_SHORTNAME        = 'task';
    final public const string USER_STORIES_TRACKER_SHORTNAME = 'story';
    final public const string DELETED_TRACKER_SHORTNAME      = 'delete';
    final public const string KANBAN_TRACKER_SHORTNAME       = 'kanbantask';

    final public const string LEVEL_ONE_TRACKER_SHORTNAME   = 'LevelOne';
    final public const string LEVEL_TWO_TRACKER_SHORTNAME   = 'LevelTwo';
    final public const string LEVEL_THREE_TRACKER_SHORTNAME = 'LevelThree';
    final public const string LEVEL_FOUR_TRACKER_SHORTNAME  = 'LevelFour';

    final public const string SUSPENDED_TRACKER_SHORTNAME = 'suspended_tracker';

    final public const string NIVEAU_1_TRACKER_SHORTNAME = 'niveau1';
    final public const string NIVEAU_2_TRACKER_SHORTNAME = 'niveau2';
    final public const string POKEMON_TRACKER_SHORTNAME  = 'pokemon';

    final public const int KANBAN_ID = 1;

    final public const int KANBAN_TO_BE_DONE_COLUMN_ID  = 230;
    final public const int KANBAN_ONGOING_COLUMN_ID     = 231;
    final public const int KANBAN_REVIEW_COLUMN_ID      = 232;
    final public const int KANBAN_OTHER_VALUE_COLUMN_ID = 233;

    final public const int PLANNING_ID = 2;

    final public const int PHPWIKI_PAGE_ID       = 6097;
    final public const int PHPWIKI_SPACE_PAGE_ID = 6100;

    protected TrackerFactory $tracker_factory;

    protected string $template_path;

    public function __construct()
    {
        parent::__construct();

        $this->template_path = __DIR__ . '/../../rest/_fixtures/';
    }

    public function instanciateFactories(): self
    {
        $this->tracker_factory = TrackerFactory::instance();

        return $this;
    }

    public function initPlugins(): void
    {
        $plugins_init = glob(__DIR__ . '/../../../plugins/*/tests/rest/init_test_data.php');
        if ($plugins_init === false) {
            return;
        }
        foreach ($plugins_init as $init_file) {
            echo "Init data from $init_file\n";
            (new \Symfony\Component\Process\Process([__DIR__ . '/../../../src/utils/php-launcher.sh', $init_file]))->mustRun();
        }
    }

    public function generateUsers(): self
    {
        $this->initPassword(self::ADMIN_USER_NAME, self::STANDARD_PASSWORD);
        $this->initPassword(self::TEST_USER_1_NAME, self::STANDARD_PASSWORD);
        $this->initPassword(self::TEST_USER_3_NAME, self::STANDARD_PASSWORD);
        $this->initPassword(self::TEST_USER_4_NAME, self::STANDARD_PASSWORD);
        $this->initPassword(self::TEST_USER_CATCH_ALL_PROJECT_ADMIN, self::STANDARD_PASSWORD);

        $user_2 = $this->getUserOrThrow(self::TEST_USER_2_NAME);
        $user_2->setPassword(new ConcealedString(self::TEST_USER_2_PASS));
        $user_2->setAuthorizedKeys('ssh-rsa AAAAB3NzaC1yc2EAAAADAQABAAABAQDHk9 toto@marche');
        $this->user_manager->updateDb($user_2);

        $user_5 = $this->getUserOrThrow(self::TEST_USER_5_NAME);
        $user_5->setPassword(new ConcealedString(self::TEST_USER_5_PASS));
        $this->user_manager->updateDb($user_5);

        $delegated_rest_project_manager = $this->getUserOrThrow(self::TEST_USER_DELEGATED_REST_PROJECT_MANAGER_NAME);
        $delegated_rest_project_manager->setPassword(
            new ConcealedString(self::TEST_USER_DELEGATED_REST_PROJECT_MANAGER_PASS)
        );
        $this->user_manager->updateDb($delegated_rest_project_manager);

        $bot_rest_read_only_admin = new PFUser();
        $bot_rest_read_only_admin->setUserName(self::TEST_BOT_USER_NAME);
        $bot_rest_read_only_admin->setPassword(new ConcealedString(self::TEST_BOT_USER_PASS));
        $bot_rest_read_only_admin->setStatus(self::TEST_BOT_USER_STATUS);
        $bot_rest_read_only_admin->setEmail(self::TEST_BOT_USER_MAIL);
        $bot_rest_read_only_admin->setLanguage($GLOBALS['Language']);
        $this->user_manager->createAccount($bot_rest_read_only_admin);

        return $this;
    }

    protected function initPassword(string $username, string $password): void
    {
        $user = $this->getUserOrThrow($username);
        $user->setPassword(new ConcealedString($password));
        $this->user_manager->updateDb($user);
    }

    public function delegateForgePermissions(): self
    {
        $forge_permission_delegate = $this->getUserOrThrow(self::TEST_USER_3_NAME);
        $manage_users_permission   = new User_ForgeUserGroupPermission_UserManagement();
        $this->delegatePermissionToUser(
            $forge_permission_delegate,
            $manage_users_permission,
            'site remote admins'
        );

        $rest_project_management_delegate       = $this->getUserOrThrow(self::TEST_USER_DELEGATED_REST_PROJECT_MANAGER_NAME);
        $manage_project_through_rest_permission = new RestProjectManagementPermission();
        $this->delegatePermissionToUser(
            $rest_project_management_delegate,
            $manage_project_through_rest_permission,
            'REST projects managers'
        );

        $rest_read_only_bot_user         = $this->getUserOrThrow(self::TEST_BOT_USER_NAME);
        $rest_read_only_admin_permission = new RestReadOnlyAdminPermission();
        $this->delegatePermissionToUser(
            $rest_read_only_bot_user,
            $rest_read_only_admin_permission,
            'REST read only administrators'
        );

        return $this;
    }

    private function delegatePermissionToUser(
        PFUser $user,
        User_ForgeUserGroupPermission_UserManagement|RestProjectManagementPermission|RestReadOnlyAdminPermission $forge_ugroup_permission,
        string $forge_ugroup_name,
    ): void {
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

    public function deleteTracker(): self
    {
        echo "Delete tracker\n";

        $tracker = $this->getDeletedTracker();

        $this->tracker_factory->markAsDeleted($tracker->getId());

        return $this;
    }

    public function deleteProject(): self
    {
        echo 'Delete deleted-project';

        $project_manager = ProjectManager::instance();

        $project = $project_manager->getProjectByUnixName('deleted-project');
        $project_manager->updateStatus($project, \Project::STATUS_DELETED);

        return $this;
    }

    public function markProjectsAsTemplate(): self
    {
        echo 'Mark public-template and private-template as template';

        $project_manager  = ProjectManager::instance();
        $public_template  = $project_manager->getProjectByUnixName('public-template');
        $private_template = $project_manager->getProjectByUnixName('private-template');

        $project_edit_dao = new ProjectEditDao();
        if ($public_template) {
            $project_edit_dao->updateProjectStatusAndType('A', TemplateSingleton::TEMPLATE, $public_template->getID());
        }

        if ($private_template) {
            $project_edit_dao->updateProjectStatusAndType('A', TemplateSingleton::TEMPLATE, $private_template->getID());
        }

        return $this;
    }

    public function createProjectField(): void
    {
        $project_fields_dao  = new ProjectFieldsDao();
        $project_details_dao = new ProjectDetailsDAO();
        $project_fields_dao->createProjectField('Test Rest', 'Field for test rest', 2, 'text', false);
        $project_details_dao->createGroupDescription(self::DEFAULT_TEMPLATE_PROJECT_ID, 1, 'Admin test');
    }

    public function suspendProject(): self
    {
        echo 'Suspend supended-project';

        $project_manager = ProjectManager::instance();

        $project = $project_manager->getProjectByUnixName('suspended-project');
        $project_manager->updateStatus($project, \Project::STATUS_SUSPENDED);

        return $this;
    }

    private function getDeletedTracker(): Tracker
    {
        return $this->getTrackerInProjectPrivateMember(self::DELETED_TRACKER_SHORTNAME);
    }

    /**
     * @psalm-param 'delete'|'epic' $tracker_shortname
     */
    protected function getTrackerInProjectPrivateMember(string $tracker_shortname): Tracker
    {
        return $this->getTrackerInProject($tracker_shortname, self::PROJECT_PRIVATE_MEMBER_SHORTNAME);
    }

    protected function getTrackerInProject(string $tracker_shortname, string $project_shortname): Tracker
    {
        $project = $this->project_manager->getProjectByUnixName($project_shortname);
        foreach ($this->tracker_factory->getTrackersByGroupId((int) $project->getID()) as $tracker) {
            if ($tracker->getItemName() === $tracker_shortname) {
                return $tracker;
            }
        }

        throw new \RuntimeException('Data seems not correctly initialized');
    }

    /**
     * @throws \RuntimeException
     */
    protected function getUserOrThrow(string $user_name): PFUser
    {
        $user = $this->user_manager->getUserByUserName($user_name);
        if ($user === null) {
            throw new \RuntimeException(sprintf('Could not find user with name "%s"', $user_name));
        }
        return $user;
    }
}
