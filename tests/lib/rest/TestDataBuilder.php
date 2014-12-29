<?php
/**
 * Copyright (c) Enalean, 2013 - 2014. All rights reserved
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

    const ADMIN_ID         = 101;
    const ADMIN_USER_NAME  = 'admin';
    const ADMIN_USER_PASS  = 'siteadmin';

    const ADMIN_REAL_NAME      = 'Site Administrator';
    const ADMIN_EMAIL          = 'codendi-admin@_DOMAIN_NAME_';
    const TEST_USER_1_ID       = 102;
    const TEST_USER_1_NAME     = 'rest_api_tester_1';
    const TEST_USER_1_REALNAME = 'Test User 1';
    const TEST_USER_1_PASS     = 'welcome0';
    const TEST_USER_1_EMAIL    = 'test_user_1@myzupermail.com';
    const TEST_USER_1_LDAPID   = 'tester1';
    const TEST_USER_2_ID       = 103;
    const TEST_USER_2_NAME     = 'rest_api_tester_2';
    const TEST_USER_2_PASS     = 'welcome0';
    const TEST_USER_3_ID       = 104;
    const TEST_USER_3_NAME     = 'rest_api_tester_3';
    const TEST_USER_3_PASS     = 'welcome0';

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

    const DYNAMIC_UGROUP_PROJECT_MEMBERS_ID    = 3;
    const DYNAMIC_UGROUP_PROJECT_MEMBERS_LABEL = 'project_members';
    const DYNAMIC_UGROUP_PROJECT_ADMINS_ID     = 4;
    const DYNAMIC_UGROUP_PROJECT_ADMINS_LABEL  = 'project_admins';
    const DYNAMIC_UGROUP_FILE_MANAGER_ID       = 11;
    const DYNAMIC_UGROUP_FILE_MANAGER_LABEL    = 'file_manager_admins';
    const DYNAMIC_UGROUP_DOCUMENT_TECH_ID      = 12;
    const DYNAMIC_UGROUP_DOCUMENT_TECH_LABEL   = 'document_techs';
    const DYNAMIC_UGROUP_DOCUMENT_ADMIN_ID     = 13;
    const DYNAMIC_UGROUP_DOCUMENT_ADMIN_LABEL  = 'document_admins';
    const DYNAMIC_UGROUP_WIKI_ADMIN_ID         = 14;
    const DYNAMIC_UGROUP_WIKI_ADMIN_LABEL      = 'wiki_admins';

    const EPICS_TRACKER_ID        = 1;
    const RELEASES_TRACKER_ID     = 2;
    const SPRINTS_TRACKER_ID      = 3;
    const TASKS_TRACKER_ID        = 4;
    const USER_STORIES_TRACKER_ID = 5;
    const DELETED_TRACKER_ID      = 6;
    const KANBAN_TRACKER_ID       = 7;

    const RELEASE_ARTIFACT_ID     = 1;
    const SPRINT_ARTIFACT_ID      = 2;
    const EPIC_1_ARTIFACT_ID      = 3;
    const EPIC_2_ARTIFACT_ID      = 4;
    const EPIC_3_ARTIFACT_ID      = 5;
    const EPIC_4_ARTIFACT_ID      = 6;
    const STORY_1_ARTIFACT_ID     = 7;
    const STORY_2_ARTIFACT_ID     = 8;
    const STORY_3_ARTIFACT_ID     = 9;
    const STORY_4_ARTIFACT_ID     = 10;
    const STORY_5_ARTIFACT_ID     = 11;
    const STORY_6_ARTIFACT_ID     = 12;
    const EPIC_5_ARTIFACT_ID      = 13;
    const EPIC_6_ARTIFACT_ID      = 14;
    const EPIC_7_ARTIFACT_ID      = 15;

    /** @var ProjectManager */
    protected $project_manager;

    /** @var UserManager */
    protected $user_manager;

    /** @var ProjectCreator */
    private $project_creator;

    /** @var UserPermissionsDao */
    private $user_permissions_dao;

    /** @var Tracker_ArtifactFactory */
    private $tracker_artifact_factory;

    /** @var Tracker_FormElementFactory */
    private $tracker_formelement_factory;

    /** @var TrackerFactory */
    private $tracker_factory;

    public function __construct() {
        $this->project_manager             = ProjectManager::instance();
        $this->user_manager                = UserManager::instance();
        $this->user_permissions_dao        = new UserPermissionsDao();

        $this->project_creator = new ProjectCreator(
            $this->project_manager,
            new Rule_ProjectName(),
            new Rule_ProjectFullName()
        );

        $GLOBALS['Language'] = new BaseLanguage('en_US', 'en_US');
        $GLOBALS['sys_lf'] = '\n';
    }

    public function activatePlugins() {
        $this->activatePlugin('tracker');
        $this->activatePlugin('agiledashboard');
        $this->activatePlugin('cardwall');
        PluginManager::instance()->loadPlugins();

        $this->tracker_artifact_factory    = Tracker_ArtifactFactory::instance();
        $this->tracker_formelement_factory = Tracker_FormElementFactory::instance();
        $this->tracker_factory             = TrackerFactory::instance();

        return $this;
    }

    public function initPlugins() {
        foreach (glob(dirname(__FILE__).'/../../../plugins/*/tests/rest/init_test_data.php') as $init_file) {
            require_once $init_file;
        }
    }

    protected function activatePlugin($name) {
        $plugin_factory = PluginFactory::instance();
        $plugin = $plugin_factory->createPlugin($name);
        $plugin_factory->availablePlugin($plugin);
    }

    public function activateDebug() {
	Config::set('DEBUG_MODE', 1);
        return $this;
    }

    public function generateUsers() {
        $user_1 = new PFUser();
        $user_1->setUserName(self::TEST_USER_1_NAME);
        $user_1->setRealName(self::TEST_USER_1_REALNAME);
        $user_1->setLdapId(self::TEST_USER_1_LDAPID);
        $user_1->setPassword(self::TEST_USER_1_PASS);
        $user_1->setEmail(self::TEST_USER_1_EMAIL);
        $user_1->setLanguage($GLOBALS['Language']);
        $this->user_manager->createAccount($user_1);
        $user_1->setLabFeatures(true);

        $user_2 = new PFUser();
        $user_2->setUserName(self::TEST_USER_2_NAME);
        $user_2->setPassword(self::TEST_USER_2_PASS);
        $user_2->setLanguage($GLOBALS['Language']);
        $this->user_manager->createAccount($user_2);

        $user_3 = new PFUser();
        $user_3->setUserName(self::TEST_USER_3_NAME);
        $user_3->setPassword(self::TEST_USER_3_PASS);
        $user_3->setLanguage($GLOBALS['Language']);
        $this->user_manager->createAccount($user_3);

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

    public function generateProject() {
        $GLOBALS['svn_prefix'] = '/tmp';
        $GLOBALS['cvs_prefix'] = '/tmp';
        $GLOBALS['grpdir_prefix'] = '/tmp';
        $GLOBALS['ftp_frs_dir_prefix'] = '/tmp';
        $GLOBALS['ftp_anon_dir_prefix'] = '/tmp';

        $user_test_rest_1 = $this->user_manager->getUserByUserName(self::TEST_USER_1_NAME);
        $user_test_rest_2 = $this->user_manager->getUserByUserName(self::TEST_USER_2_NAME);
        $user_test_rest_3 = $this->user_manager->getUserByUserName(self::TEST_USER_3_NAME);

        echo "Create projects\n";

        $project_1 = $this->createProject(
            self::PROJECT_PRIVATE_MEMBER_SHORTNAME,
            'Private member',
            false,
            array($user_test_rest_1, $user_test_rest_2, $user_test_rest_3),
            array($user_test_rest_1)
        );
        $this->addUserGroupsToProject($project_1);
        $this->addUserToUserGroup($user_test_rest_1, $project_1, self::STATIC_UGROUP_1_ID);
        $this->addUserToUserGroup($user_test_rest_1, $project_1, self::STATIC_UGROUP_2_ID);
        $this->addUserToUserGroup($user_test_rest_2, $project_1, self::STATIC_UGROUP_2_ID);

        $project_2 = $this->createProject(
            self::PROJECT_PRIVATE_SHORTNAME,
            'Private',
            false,
            array(),
            array()
        );
        $this->importTemplateInProject(self::PROJECT_PRIVATE_MEMBER_ID, 'tuleap_agiledashboard_template.xml');
        $this->importTemplateInProject(self::PROJECT_PRIVATE_MEMBER_ID, 'tuleap_agiledashboard_kanban_template.xml');

        $project_3 = $this->createProject(
            self::PROJECT_PUBLIC_SHORTNAME,
            'Public',
            true,
            array(),
            array()
        );

        $project_4 = $this->createProject(
            self::PROJECT_PUBLIC_MEMBER_SHORTNAME,
            'Public member',
            true,
            array($user_test_rest_1),
            array()
        );

        $pbi = $this->createProject(
            self::PROJECT_PBI_SHORTNAME,
            'PBI',
            true,
            array($user_test_rest_1),
            array()
        );
        $this->importTemplateInProject($pbi->getId(), 'tuleap_agiledashboard_template_pbi_6348.xml');

        $backlog = $this->createProject(
            self::PROJECT_BACKLOG_DND,
            'Backlog drag and drop',
            true,
            array($user_test_rest_1),
            array()
        );
        $this->importTemplateInProject($backlog->getId(), 'tuleap_agiledashboard_template.xml');

        unset($GLOBALS['svn_prefix']);
        unset($GLOBALS['cvs_prefix']);
        unset($GLOBALS['grpdir_prefix']);
        unset($GLOBALS['ftp_frs_dir_prefix']);
        unset($GLOBALS['ftp_anon_dir_prefix']);

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
                'services'               => array(),
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

    private function addUserToUserGroup($user, $project, $ugroup_id) {
        ugroup_add_user_to_ugroup($project->getId(), $ugroup_id, $user->getId());
    }

    private function importTemplateInProject($project_id, $template) {
        $xml_importer = new ProjectXMLImporter(
            EventManager::instance(),
            $this->project_manager
        );
        $this->user_manager->forceLogin(self::ADMIN_USER_NAME);
        $xml_importer->import($project_id, dirname(__FILE__).'/../../rest/_fixtures/'.$template);
    }

    public function deleteTracker() {
        echo "Delete tracker\n";

        $this->tracker_factory->markAsDeleted(self::DELETED_TRACKER_ID);

        return $this;
    }

    public function generateMilestones() {
        echo "Create milestones\n";

        $user = $this->user_manager->getUserByUserName(self::ADMIN_USER_NAME);

        $this->createRelease($user, 'Release 1.0', '126');
        $this->createSprint(
            $user,
            'Sprint A',
            '150',
            '2014-1-9',
            '10',
            '29'
        );

        $release = $this->tracker_artifact_factory->getArtifactById(self::RELEASE_ARTIFACT_ID);
        $release->linkArtifact(self::SPRINT_ARTIFACT_ID, $user);

        return $this;
    }

    public function generateContentItems() {
        echo "Create content items\n";

        $user = $this->user_manager->getUserByUserName(self::ADMIN_USER_NAME);

        $this->createEpic($user, 'First epic', '101');
        $this->createEpic($user, 'Second epic', '102');
        $this->createEpic($user, 'Third epic', '103');
        $this->createEpic($user, 'Fourth epic', '101');

        $release = $this->tracker_artifact_factory->getArtifactById(self::RELEASE_ARTIFACT_ID);
        $release->linkArtifact(self::EPIC_1_ARTIFACT_ID, $user);
        $release->linkArtifact(self::EPIC_2_ARTIFACT_ID, $user);
        $release->linkArtifact(self::EPIC_3_ARTIFACT_ID, $user);
        $release->linkArtifact(self::EPIC_4_ARTIFACT_ID, $user);

        return $this;
    }

    public function generateBacklogItems() {
        echo "Create backlog items\n";

        $user = $this->user_manager->getUserByUserName(self::ADMIN_USER_NAME);

        $this->createUserStory($user, 'Believe', '206');
        $this->createUserStory($user, 'Break Free', '205');
        $this->createUserStory($user, 'Hughhhhhhh', '205');
        $this->createUserStory($user, 'Kill you', '205');
        $this->createUserStory($user, 'Back', '205');
        $this->createUserStory($user, 'Forward', '205');

        $release = $this->tracker_artifact_factory->getArtifactById(self::RELEASE_ARTIFACT_ID);
        $release->linkArtifact(self::STORY_1_ARTIFACT_ID, $user);
        $release->linkArtifact(self::STORY_2_ARTIFACT_ID, $user);
        $release->linkArtifact(self::STORY_3_ARTIFACT_ID, $user);
        $release->linkArtifact(self::STORY_4_ARTIFACT_ID, $user);
        $release->linkArtifact(self::STORY_5_ARTIFACT_ID, $user);

        $sprint = $this->tracker_artifact_factory->getArtifactById(self::SPRINT_ARTIFACT_ID);
        $sprint->linkArtifact(self::STORY_1_ARTIFACT_ID, $user);
        $sprint->linkArtifact(self::STORY_2_ARTIFACT_ID, $user);

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

    public function generateKanban() {
        echo "Create 'My first kanban'\n";
        $kanban_manager = new AgileDashboard_KanbanManager(new AgileDashboard_KanbanDao(), $this->tracker_factory);
        $kanban_manager->createKanban('My first kanban', self::KANBAN_TRACKER_ID);

        echo "Populate kanban\n";
        $fields_data = array(
            $this->tracker_formelement_factory->getFormElementByName(self::KANBAN_TRACKER_ID, 'summary_1')->getId() => 'Do something',
        );

        $this->tracker_artifact_factory->createArtifact(
            $this->tracker_factory->getTrackerById(self::KANBAN_TRACKER_ID),
            $fields_data,
            $this->user_manager->getUserByUserName(self::ADMIN_USER_NAME),
            '',
            false
        );

        return $this;
    }

    private function createRelease(PFUser $user, $field_name_value, $field_status_value) {
        $fields_data = array(
            $this->tracker_formelement_factory->getFormElementByName(self::RELEASES_TRACKER_ID, 'name')->getId() => $field_name_value,
            $this->tracker_formelement_factory->getFormElementByName(self::RELEASES_TRACKER_ID, 'status')->getId()  => $field_status_value
        );

        $this->tracker_artifact_factory->createArtifact(
            $this->tracker_factory->getTrackerById(self::RELEASES_TRACKER_ID),
            $fields_data,
            $user,
            '',
            false
        );

    }

    private function createEpic(PFUser $user, $field_summary_value, $field_status_value) {
        $fields_data = array(
            $this->tracker_formelement_factory->getFormElementByName(self::EPICS_TRACKER_ID, 'summary_11')->getId() => $field_summary_value,
            $this->tracker_formelement_factory->getFormElementByName(self::EPICS_TRACKER_ID, 'status')->getId()  => $field_status_value
        );

        $this->tracker_artifact_factory->createArtifact(
            $this->tracker_factory->getTrackerById(self::EPICS_TRACKER_ID),
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
        $fields_data = array(
            $this->tracker_formelement_factory->getFormElementByName(self::SPRINTS_TRACKER_ID, 'name')->getId()       => $field_name_value,
            $this->tracker_formelement_factory->getFormElementByName(self::SPRINTS_TRACKER_ID, 'status')->getId()     => $field_status_value,
            $this->tracker_formelement_factory->getFormElementByName(self::SPRINTS_TRACKER_ID, 'start_date')->getId() => $field_start_date_value,
            $this->tracker_formelement_factory->getFormElementByName(self::SPRINTS_TRACKER_ID, 'duration')->getId()   => $field_duration_value,
            $this->tracker_formelement_factory->getFormElementByName(self::SPRINTS_TRACKER_ID, 'capacity')->getId()   => $field_capacity_value,
        );

        $this->tracker_artifact_factory->createArtifact(
            $this->tracker_factory->getTrackerById(self::SPRINTS_TRACKER_ID),
            $fields_data,
            $user,
            '',
            false
        );

    }

    private function createUserStory(PFUser $user, $field_i_want_to_value, $field_status_value) {
        $fields_data = array(
            $this->tracker_formelement_factory->getFormElementByName(self::USER_STORIES_TRACKER_ID, 'i_want_to')->getId() => $field_i_want_to_value,
            $this->tracker_formelement_factory->getFormElementByName(self::USER_STORIES_TRACKER_ID, 'status')->getId()  => $field_status_value
        );

        $this->tracker_artifact_factory->createArtifact(
            $this->tracker_factory->getTrackerById(self::USER_STORIES_TRACKER_ID),
            $fields_data,
            $user,
            '',
            false
        );
    }

}
