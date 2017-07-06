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

class REST_TestDataBuilder extends TestDataBuilder {

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

    const RELEASE_FIELD_NAME_ID     = 190;
    const RELEASE_FIELD_STATUS_ID   = 192;
    const RELEASE_STATUS_CURRENT_ID = 126;

    const KANBAN_ID = 1;

    const KANBAN_TO_BE_DONE_COLUMN_ID = 230;
    const KANBAN_ONGOING_COLUMN_ID    = 231;
    const KANBAN_REVIEW_COLUMN_ID     = 232;
    const KANBAN_DONE_VALUE_ID        = 233;

    const TRACKER_REPORT_ID = 112;

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

    public function __construct() {
        parent::__construct();

        $this->template_path = dirname(__FILE__).'/../../rest/_fixtures/';
    }

    public function activatePlugins() {
        $this->activatePlugin('tracker');
        $this->activatePlugin('agiledashboard');
        $this->activatePlugin('cardwall');
        PluginManager::instance()->invalidateCache();
        PluginManager::instance()->loadPlugins();
        return $this;
    }

    public function instanciateFactories() {
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

    /**
     * @deprecated See initPlugins() and do all the data generation in the plugin
     */
    public function initPluginsLeakingInCoreTests()
    {
        foreach (glob(dirname(__FILE__).'/../../../plugins/*/tests/rest/init_test_data_leaking.php') as $init_file) {
            require_once $init_file;
        }
    }

    public function initPlugins() {
        foreach (glob(dirname(__FILE__).'/../../../plugins/*/tests/rest/init_test_data.php') as $init_file) {
            require_once $init_file;
        }
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

        $user_2 = new PFUser();
        $user_2->setUserName(self::TEST_USER_2_NAME);
        $user_2->setPassword(self::TEST_USER_2_PASS);
        $user_2->setStatus(self::TEST_USER_2_STATUS);
        $user_2->setEmail(self::TEST_USER_2_EMAIL);
        $user_2->setLanguage($GLOBALS['Language']);
        $user_2->setAuthorizedKeys('ssh-rsa AAAAB3NzaC1yc2EAAAADAQABAAABAQDHk9 toto@marche');
        $this->user_manager->createAccount($user_2);

        $user_3 = new PFUser();
        $user_3->setUserName(self::TEST_USER_3_NAME);
        $user_3->setPassword(self::TEST_USER_3_PASS);
        $user_3->setStatus(self::TEST_USER_3_STATUS);
        $user_3->setEmail(self::TEST_USER_3_EMAIL);
        $user_3->setLanguage($GLOBALS['Language']);
        $this->user_manager->createAccount($user_3);

        $user_4 = new PFUser();
        $user_4->setUserName(self::TEST_USER_4_NAME);
        $user_4->setPassword(self::TEST_USER_4_PASS);
        $user_4->setStatus(self::TEST_USER_4_STATUS);
        $user_4->setEmail(self::TEST_USER_1_EMAIL);
        $user_4->setLanguage($GLOBALS['Language']);
        $this->user_manager->createAccount($user_4);

        return $this;
    }

    public function delegatePermissionsToRetrieveMembership() {
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

    public function delegatePermissionsToManageUser() {
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

    public function generateProject() {
        $this->setGlobalsForProjectCreation();

        $user_test_rest_1 = $this->user_manager->getUserByUserName(self::TEST_USER_1_NAME);
        $user_test_rest_2 = $this->user_manager->getUserByUserName(self::TEST_USER_2_NAME);
        $user_test_rest_3 = $this->user_manager->getUserByUserName(self::TEST_USER_3_NAME);

        echo "Create projects\n";

        $project_1 = $this->createProject(
            self::PROJECT_PRIVATE_MEMBER_SHORTNAME,
            'Private member',
            false,
            array($user_test_rest_1, $user_test_rest_2, $user_test_rest_3),
            array($user_test_rest_1),
            array()
        );
        $this->addUserGroupsToProject($project_1);
        $this->addUserToUserGroup($user_test_rest_1, $project_1, self::STATIC_UGROUP_1_ID);
        $this->addUserToUserGroup($user_test_rest_1, $project_1, self::STATIC_UGROUP_2_ID);
        $this->addUserToUserGroup($user_test_rest_2, $project_1, self::STATIC_UGROUP_2_ID);

        $this->importTemplateInProject($project_1->getID(), 'tuleap_agiledashboard_template.xml');
        $this->importTemplateInProject($project_1->getID(), 'tuleap_agiledashboard_kanban_template.xml');

        $project_2 = $this->createProject(
            self::PROJECT_PRIVATE_SHORTNAME,
            'Private',
            false,
            array($user_test_rest_3),
            array($user_test_rest_3),
            array()
        );

        $project_3 = $this->createProject(
            self::PROJECT_PUBLIC_SHORTNAME,
            'Public',
            true,
            array(),
            array(),
            array()
        );

        $project_4 = $this->createProject(
            self::PROJECT_PUBLIC_MEMBER_SHORTNAME,
            'Public member',
            true,
            array($user_test_rest_1),
            array(),
            array()
        );

        $pbi = $this->createProject(
            self::PROJECT_PBI_SHORTNAME,
            'PBI',
            true,
            array($user_test_rest_1),
            array(),
            array()
        );
        $this->importTemplateInProject($pbi->getId(), 'tuleap_agiledashboard_template_pbi_6348.xml');

        $backlog = $this->createProject(
            self::PROJECT_BACKLOG_DND,
            'Backlog drag and drop',
            true,
            array($user_test_rest_1),
            array($user_test_rest_1),
            array()
        );
        $this->importTemplateInProject($backlog->getId(), 'tuleap_agiledashboard_template.xml');

        $computed_field_project = $this->createProject(
            self::PROJECT_COMPUTED_FIELDS,
            'Computed Fields',
            true,
            array($user_test_rest_1),
            array($user_test_rest_1),
            array()
        );
        $this->importTemplateInProject($computed_field_project->getId(), 'tuleap_computedfields_template.xml');

        $this->unsetGlobalsForProjectCreation();

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

    public function deleteTracker() {
        echo "Delete tracker\n";

        $tracker = $this->getDeletedTracker();

        $this->tracker_factory->markAsDeleted($tracker->getId());

        return $this;
    }

    public function generateMilestones() {
        echo "Create milestones\n";

        $this->clearFormElementCache();

        $user = $this->user_manager->getUserByUserName(self::ADMIN_USER_NAME);

        $this->createRelease($user, 'Release 1.0', '126');
        $this->sprint = $this->createSprint(
            $user,
            'Sprint A',
            '150',
            '2014-1-9',
            '10',
            '29'
        );

        $this->release->linkArtifact($this->sprint->getId(), $user);

        return $this;
    }

    private function clearFormElementCache() {
        $this->tracker_formelement_factory->clearInstance();
        $this->tracker_formelement_factory->instance();
    }

    public function generateContentItems() {
        echo "Create content items\n";

        $user = $this->user_manager->getUserByUserName(self::ADMIN_USER_NAME);

        $epic_1 = $this->createEpic($user, 'First epic', '101');
        $epic_2 = $this->createEpic($user, 'Second epic', '102');
        $epic_3 = $this->createEpic($user, 'Third epic', '103');
        $epic_4 = $this->createEpic($user, 'Fourth epic', '101');

        $this->release->linkArtifact($epic_1->getId(), $user);
        $this->release->linkArtifact($epic_2->getId(), $user);
        $this->release->linkArtifact($epic_3->getId(), $user);
        $this->release->linkArtifact($epic_4->getId(), $user);

        return $this;
    }

    public function generateBacklogItems() {
        echo "Create backlog items\n";

        $user = $this->user_manager->getUserByUserName(self::ADMIN_USER_NAME);

        $story_1 = $this->createUserStory($user, 'Believe', '206');
        $story_2 = $this->createUserStory($user, 'Break Free', '205');
        $story_3 = $this->createUserStory($user, 'Hughhhhhhh', '205');
        $story_4 = $this->createUserStory($user, 'Kill you', '205');
        $story_5 = $this->createUserStory($user, 'Back', '205');
        $this->createUserStory($user, 'Forward', '205');

        $this->release->linkArtifact($story_1->getId(), $user);
        $this->release->linkArtifact($story_2->getId(), $user);
        $this->release->linkArtifact($story_3->getId(), $user);
        $this->release->linkArtifact($story_4->getId(), $user);
        $this->release->linkArtifact($story_5->getId(), $user);

        $this->sprint->linkArtifact($story_1->getId(), $user);
        $this->sprint->linkArtifact($story_2->getId(), $user);

        return $this;
    }

    public function generateTopBacklogItems() {
        echo "Create top backlog items\n";

        $user = $this->user_manager->getUserByUserName(self::ADMIN_USER_NAME);

        $this->createEpic($user, 'Epic pic', '101');
        $this->createEpic($user, "Epic c'est tout", '101');
        $this->createEpic($user, 'Epic epoc', '101');

        return $this;
    }

    /**
     * @return Tracker
     */
    private function getEpicTracker()
    {
        return $this->getTrackerInProjectPrivateMember(self::EPICS_TRACKER_SHORTNAME);
    }

    /**
     * @return Tracker
     */
    private function getReleaseTracker()
    {
        return $this->getTrackerInProjectPrivateMember(self::RELEASES_TRACKER_SHORTNAME);
    }

    /**
     * @return Tracker
     */
    private function getSprintTracker()
    {
        return $this->getTrackerInProjectPrivateMember(self::SPRINTS_TRACKER_SHORTNAME);
    }

    /**
     * @return Tracker
     */
    private function getUserStoryTracker()
    {
        return $this->getTrackerInProjectPrivateMember(self::USER_STORIES_TRACKER_SHORTNAME);
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

    public function generateKanban() {
        echo "Create 'My first kanban'\n";

        $tracker    = $this->getKanbanTracker();
        $tracker_id = $tracker->getId();

        $kanban_manager = new AgileDashboard_KanbanManager(new AgileDashboard_KanbanDao(), $this->tracker_factory, $this->hierarchy_checker);
        $kanban_manager->createKanban('My first kanban', $tracker_id);

        echo "Populate kanban\n";
        $fields_data = array(
            $this->tracker_formelement_factory->getFormElementByName($tracker_id, 'summary_1')->getId() => 'Do something',
            $this->tracker_formelement_factory->getFormElementByName($tracker_id, 'status')->getId() => 100,
        );

        $this->tracker_artifact_factory->createArtifact(
            $this->tracker_factory->getTrackerById($tracker_id),
            $fields_data,
            $this->user_manager->getUserByUserName(self::ADMIN_USER_NAME),
            '',
            false
        );

        $fields_data = array(
            $this->tracker_formelement_factory->getFormElementByName($tracker_id, 'summary_1')->getId() => 'Do something v2',
            $this->tracker_formelement_factory->getFormElementByName($tracker_id, 'status')->getId() => 100,
        );

        $this->tracker_artifact_factory->createArtifact(
            $this->tracker_factory->getTrackerById($tracker_id),
            $fields_data,
            $this->user_manager->getUserByUserName(self::ADMIN_USER_NAME),
            '',
            false
        );

        $fields_data = array(
            $this->tracker_formelement_factory->getFormElementByName($tracker_id, 'summary_1')->getId() => 'Doing something',
            $this->tracker_formelement_factory->getFormElementByName($tracker_id, 'status')->getId() => self::KANBAN_ONGOING_COLUMN_ID,
        );

        $this->tracker_artifact_factory->createArtifact(
            $this->tracker_factory->getTrackerById($tracker_id),
            $fields_data,
            $this->user_manager->getUserByUserName(self::ADMIN_USER_NAME),
            '',
            false
        );

        $fields_data = array(
            $this->tracker_formelement_factory->getFormElementByName($tracker_id, 'summary_1')->getId() => 'Doing something v2',
            $this->tracker_formelement_factory->getFormElementByName($tracker_id, 'status')->getId() => self::KANBAN_ONGOING_COLUMN_ID,
        );

        $this->tracker_artifact_factory->createArtifact(
            $this->tracker_factory->getTrackerById($tracker_id),
            $fields_data,
            $this->user_manager->getUserByUserName(self::ADMIN_USER_NAME),
            '',
            false
        );

        $fields_data = array(
            $this->tracker_formelement_factory->getFormElementByName($tracker_id, 'summary_1')->getId() => 'Something archived',
            $this->tracker_formelement_factory->getFormElementByName($tracker_id, 'status')->getId() => self::KANBAN_DONE_VALUE_ID,
        );

        $this->tracker_artifact_factory->createArtifact(
            $this->tracker_factory->getTrackerById($tracker_id),
            $fields_data,
            $this->user_manager->getUserByUserName(self::ADMIN_USER_NAME),
            '',
            false
        );

        $fields_data = array(
            $this->tracker_formelement_factory->getFormElementByName($tracker_id, 'summary_1')->getId() => 'Something archived v2',
            $this->tracker_formelement_factory->getFormElementByName($tracker_id, 'status')->getId() => self::KANBAN_DONE_VALUE_ID,
        );

        $this->tracker_artifact_factory->createArtifact(
            $this->tracker_factory->getTrackerById($tracker_id),
            $fields_data,
            $this->user_manager->getUserByUserName(self::ADMIN_USER_NAME),
            '',
            false
        );

        return $this;
    }

    private function createRelease(PFUser $user, $field_name_value, $field_status_value) {
        $tracker    = $this->getReleaseTracker();
        $tracker_id = $tracker->getId();

        $fields_data = array(
            $this->tracker_formelement_factory->getFormElementByName($tracker_id, 'name')->getId() => $field_name_value,
            $this->tracker_formelement_factory->getFormElementByName($tracker_id, 'status')->getId()  => $field_status_value
        );

        $this->release = $this->tracker_artifact_factory->createArtifact(
            $tracker,
            $fields_data,
            $user,
            '',
            false
        );
    }

    private function createEpic(PFUser $user, $field_summary_value, $field_status_value) {
        $tracker    = $this->getEpicTracker();
        $tracker_id = $tracker->getId();

        $fields_data = array(
            $this->tracker_formelement_factory->getFormElementByName($tracker_id, 'summary_11')->getId() => $field_summary_value,
            $this->tracker_formelement_factory->getFormElementByName($tracker_id, 'status')->getId()  => $field_status_value
        );

        return $this->tracker_artifact_factory->createArtifact(
            $tracker,
            $fields_data,
            $user,
            '',
            false
        );
    }

    private function createSprint(
        PFUser $user,
        $field_name_value,
        $field_status_value,
        $field_start_date_value,
        $field_duration_value,
        $field_capacity_value
    ) {
        $tracker    = $this->getSprintTracker();
        $tracker_id = $tracker->getId();

        $fields_data = array(
            $this->tracker_formelement_factory->getFormElementByName($tracker_id, 'name')->getId()       => $field_name_value,
            $this->tracker_formelement_factory->getFormElementByName($tracker_id, 'status')->getId()     => $field_status_value,
            $this->tracker_formelement_factory->getFormElementByName($tracker_id, 'start_date')->getId() => $field_start_date_value,
            $this->tracker_formelement_factory->getFormElementByName($tracker_id, 'duration')->getId()   => $field_duration_value,
            $this->tracker_formelement_factory->getFormElementByName($tracker_id, 'capacity')->getId()   => $field_capacity_value,
        );

        return $this->tracker_artifact_factory->createArtifact(
            $tracker,
            $fields_data,
            $user,
            '',
            false
        );
    }

    private function createUserStory(PFUser $user, $field_i_want_to_value, $field_status_value) {
        $tracker    = $this->getUserStoryTracker();
        $tracker_id = $tracker->getId();

        $fields_data = array(
            $this->tracker_formelement_factory->getFormElementByName($tracker_id, 'i_want_to')->getId() => $field_i_want_to_value,
            $this->tracker_formelement_factory->getFormElementByName($tracker_id, 'status')->getId()    => $field_status_value
        );

        return $this->tracker_artifact_factory->createArtifact(
            $tracker,
            $fields_data,
            $user,
            '',
            false
        );
    }
}
