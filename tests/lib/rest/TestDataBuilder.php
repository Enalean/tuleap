<?php
/**
 * Copyright (c) Enalean, 2013 - 2017. All rights reserved
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

use Tuleap\FRS\FRSPermissionCreator;
use Tuleap\FRS\FRSPermissionDao;
use Tuleap\Project\UserRemover;
use Tuleap\Project\UserRemoverDao;

class REST_TestDataBuilder extends TestDataBuilder
{

    const TEST_USER_4_ID          = 105;
    const TEST_USER_4_NAME        = 'rest_api_tester_4';
    const TEST_USER_4_PASS        = 'welcome0';
    const TEST_USER_4_STATUS      = 'A';

    const EPICS_TRACKER_SHORTNAME        = 'epic';
    const RELEASES_TRACKER_SHORTNAME     = 'rel';
    const SPRINTS_TRACKER_SHORTNAME      = 'sprint';
    const TASKS_TRACKER_SHORTNAME        = 'task';
    const USER_STORIES_TRACKER_SHORTNAME = 'story';
    const DELETED_TRACKER_SHORTNAME      = 'delete';
    const KANBAN_TRACKER_SHORTNAME       = 'kanbantask';

    const LEVEL_ONE_TRACKER_SHORTNAME    = 'LevelOne';
    const LEVEL_TWO_TRACKER_SHORTNAME    = 'LevelTwo';
    const LEVEL_THREE_TRACKER_SHORTNAME  = 'LevelThree';
    const LEVEL_FOUR_TRACKER_SHORTNAME   = 'LevelFour';

    const NIVEAU_1_TRACKER_SHORTNAME = 'niveau1';
    const NIVEAU_2_TRACKER_SHORTNAME = 'niveau2';
    const POKEMON_TRACKER_SHORTNAME  = 'pokemon';

    const KANBAN_ID = 1;

    const KANBAN_TO_BE_DONE_COLUMN_ID = 230;
    const KANBAN_ONGOING_COLUMN_ID    = 231;
    const KANBAN_REVIEW_COLUMN_ID     = 232;
    const KANBAN_DONE_VALUE_ID        = 233;

    const PLANNING_ID = 2;

    const PHPWIKI_PAGE_ID          = 6097;
    const PHPWIKI_SPACE_PAGE_ID    = 6100;

    /** @var Tracker_ArtifactFactory */
    private $tracker_artifact_factory;

    /** @var Tracker_FormElementFactory */
    private $tracker_formelement_factory;

    /** @var TrackerFactory */
    private $tracker_factory;

    /** @var AgileDashboard_HierarchyChecker */
    private $hierarchy_checker;

    /** @var string */
    protected $template_path;

    protected $release;
    protected $sprint;

    public function __construct()
    {
        parent::__construct();

        $this->template_path = dirname(__FILE__).'/../../rest/_fixtures/';
    }

    public function instanciateFactories()
    {
        $this->tracker_artifact_factory    = Tracker_ArtifactFactory::instance();
        $this->tracker_formelement_factory = Tracker_FormElementFactory::instance();
        $this->tracker_factory             = TrackerFactory::instance();
        $this->hierarchy_checker           = new AgileDashboard_HierarchyChecker(
            PlanningFactory::build(),
            new AgileDashboard_KanbanFactory($this->tracker_factory, new AgileDashboard_KanbanDao()),
            $this->tracker_factory
        );

        return $this;
    }

    public function initPlugins()
    {
        foreach (glob(dirname(__FILE__).'/../../../plugins/*/tests/rest/init_test_data.php') as $init_file) {
            require_once $init_file;
        }
    }

    public function generateUsers()
    {
        $user_1 = $this->user_manager->getUserByUserName(self::TEST_USER_1_NAME);
        $user_1->setPassword(self::TEST_USER_1_PASS);
        $this->user_manager->updateDb($user_1);

        $user_2 = $this->user_manager->getUserByUserName(self::TEST_USER_2_NAME);
        $user_2->setPassword(self::TEST_USER_2_PASS);
        $user_2->setAuthorizedKeys('ssh-rsa AAAAB3NzaC1yc2EAAAADAQABAAABAQDHk9 toto@marche');
        $this->user_manager->updateDb($user_2);

        $user_3 = $this->user_manager->getUserByUserName(self::TEST_USER_3_NAME);
        $user_3->setPassword(self::TEST_USER_3_PASS);
        $this->user_manager->updateDb($user_3);

        $user_4 = $this->user_manager->getUserByUserName(self::TEST_USER_3_NAME);
        $user_4->setPassword(self::TEST_USER_4_PASS);
        $this->user_manager->updateDb($user_4);

        return $this;
    }

    public function delegatePermissionsToRetrieveMembership()
    {
        $user = $this->user_manager->getUserById(self::TEST_USER_3_ID);

        // Create group
        $user_group_dao     = new UserGroupDao();
        $user_group_factory = new User_ForgeUserGroupFactory($user_group_dao);
        $user_group         = $user_group_factory->createForgeUGroup('grokmirror users', '');

        // Grant Retrieve Membership permissions
        $permission                     = new User_ForgeUserGroupPermission_RetrieveUserMembershipInformation();
        $permissions_dao                = new User_ForgeUserGroupPermissionsDao();
        $user_group_permissions_manager = new User_ForgeUserGroupPermissionsManager($permissions_dao);
        $user_group_permissions_manager->addPermission($user_group, $permission);

        // Add user to group
        $user_group_users_dao     = new User_ForgeUserGroupUsersDao();
        $user_group_users_manager = new User_ForgeUserGroupUsersManager($user_group_users_dao);
        $user_group_users_manager->addUserToForgeUserGroup($user, $user_group);

        return $this;
    }

    public function delegatePermissionsToManageUser()
    {
        $user = $this->user_manager->getUserById(self::TEST_USER_3_ID);

        // Create group
        $user_group_dao     = new UserGroupDao();
        $user_group_factory = new User_ForgeUserGroupFactory($user_group_dao);
        $user_group         = $user_group_factory->createForgeUGroup('site remote admins', '');

        // Grant Retrieve Membership permissions
        $permission                     = new User_ForgeUserGroupPermission_UserManagement();
        $permissions_dao                = new User_ForgeUserGroupPermissionsDao();
        $user_group_permissions_manager = new User_ForgeUserGroupPermissionsManager($permissions_dao);
        $user_group_permissions_manager->addPermission($user_group, $permission);

        // Add user to group
        $user_group_users_dao     = new User_ForgeUserGroupUsersDao();
        $user_group_users_manager = new User_ForgeUserGroupUsersManager($user_group_users_dao);
        $user_group_users_manager->addUserToForgeUserGroup($user, $user_group);

        return $this;
    }

    protected function importTemplateInProject($project_id, $template)
    {
        $xml_importer = new ProjectXMLImporter(
            EventManager::instance(),
            $this->project_manager,
            UserManager::instance(),
            new XML_RNGValidator(),
            new UGroupManager(),
            new XMLImportHelper(UserManager::instance()),
            ServiceManager::instance(),
            new ProjectXMLImporterLogger(),
            $this->ugroup_duplicator,
            new FRSPermissionCreator(
                new FRSPermissionDao(),
                new UGroupDao()
            ),
            new UserRemover(
                ProjectManager::instance(),
                EventManager::instance(),
                new ArtifactTypeFactory(false),
                new UserRemoverDao(),
                UserManager::instance(),
                new ProjectHistoryDao(),
                new UGroupManager()
            ),
            $this->project_creator
        );

        $this->user_manager->forceLogin(self::ADMIN_USER_NAME);
        $xml_importer->import(new \Tuleap\Project\XML\Import\ImportConfig(), $project_id, $this->template_path.$template);
    }

    public function deleteTracker()
    {
        echo "Delete tracker\n";

        $tracker = $this->getDeletedTracker();

        $this->tracker_factory->markAsDeleted($tracker->getId());

        return $this;
    }

    /**
     * @return Tracker
     */
    private function getDeletedTracker()
    {
        return $this->getTrackerInProjectPrivateMember(self::DELETED_TRACKER_SHORTNAME);
    }

    /**
     * @return Tracker
     */
    private function getKanbanTracker()
    {
        return $this->getTrackerInProjectPrivateMember(self::KANBAN_TRACKER_SHORTNAME);
    }

    private function getTrackerInProjectPrivateMember($tracker_shortname)
    {
        return $this->getTrackerInProject($tracker_shortname, self::PROJECT_PRIVATE_MEMBER_SHORTNAME);
    }

    private function getTrackerInProject($tracker_shortname, $project_shortname)
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

    public function generateKanban()
    {
        echo "Create 'My first kanban'\n";

        $tracker    = $this->getKanbanTracker();
        $tracker_id = $tracker->getId();

        $kanban_manager = new AgileDashboard_KanbanManager(new AgileDashboard_KanbanDao(), $this->tracker_factory, $this->hierarchy_checker);
        $kanban_manager->createKanban('My first kanban', $tracker_id);

        return $this;
    }
}
