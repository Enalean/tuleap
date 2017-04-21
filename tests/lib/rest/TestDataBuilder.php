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
use Tuleap\Tracker\FormElement\Field\ArtifactLink\Nature\NatureDao;

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

    const RELEASE_FIELD_NAME_ID     = 171;
    const RELEASE_FIELD_STATUS_ID   = 173;
    const RELEASE_STATUS_CURRENT_ID = 126;

    const KANBAN_ID            = 1;

    const KANBAN_ARTIFACT_ID_2 = 35;
    const KANBAN_ARTIFACT_ID_3 = 36;
    const KANBAN_ARTIFACT_ID_4 = 37;
    const KANBAN_ARTIFACT_ID_5 = 38;
    const KANBAN_ARTIFACT_ID_6 = 39;

    const BURNDOWN_FATHER_ARTIFACT_ID  = 12;
    const BURNDOWN_CHILD_ARTIFACT_ID   = 1;
    const BURNDOWN_CHILD_2_ARTIFACT_ID = 8;

    const RELEASE_ARTIFACT_ID       = 19;
    const SPRINT_ARTIFACT_ID        = 20;
    const EPIC_1_ARTIFACT_ID        = 21;
    const EPIC_2_ARTIFACT_ID        = 22;
    const EPIC_3_ARTIFACT_ID        = 23;
    const EPIC_4_ARTIFACT_ID        = 24;
    const STORY_1_ARTIFACT_ID       = 25;
    const STORY_2_ARTIFACT_ID       = 26;
    const STORY_3_ARTIFACT_ID       = 27;
    const STORY_4_ARTIFACT_ID       = 28;
    const STORY_5_ARTIFACT_ID       = 29;
    const STORY_6_ARTIFACT_ID       = 30;
    const EPIC_5_ARTIFACT_ID        = 31;
    const EPIC_6_ARTIFACT_ID        = 32;
    const EPIC_7_ARTIFACT_ID        = 33;

    const LEVEL_ONE_ARTIFACT_A_ID   = 40;

    const LEVEL_TWO_ARTIFACT_B_ID   = 41;
    const LEVEL_TWO_ARTIFACT_C_ID   = 42;

    const LEVEL_THREE_ARTIFACT_D_ID = 43;
    const LEVEL_THREE_ARTIFACT_E_ID = 44;
    const LEVEL_THREE_ARTIFACT_F_ID = 45;

    const LEVEL_FOUR_ARTIFACT_G_ID  = 46;
    const LEVEL_FOUR_ARTIFACT_H_ID  = 47;

    const KANBAN_TO_BE_DONE_COLUMN_ID = 230;
    const KANBAN_ONGOING_COLUMN_ID    = 231;
    const KANBAN_REVIEW_COLUMN_ID     = 232;
    const KANBAN_DONE_VALUE_ID        = 233;

    const TRACKER_REPORT_ID = 108;

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

    public function generateComputedFieldTree()
    {
        echo "Create computed field tree\n";

        $user                = $this->user_manager->getUserByUserName(self::ADMIN_USER_NAME);
        $tracker_level_one   = $this->getLevelOneTracker();
        $tracker_level_two   = $this->getLevelTwoTracker();
        $tracker_level_three = $this->getLevelThreeTracker();
        $tracker_level_four  = $this->getLevelFourTracker();

        $artifact_a = $this->createEmptyArtifact($user, 'A', $tracker_level_one->getId());

        $artifact_b = $this->createEmptyArtifact($user, 'B', $tracker_level_two->getId());
        $artifact_c = $this->createEmptyArtifact($user, 'C', $tracker_level_two->getId());

        $artifact_d = $this->createEmptyArtifact($user, 'D', $tracker_level_three->getId());
        $artifact_e = $this->createEmptyArtifact($user, 'E', $tracker_level_three->getId());
        $artifact_f = $this->createEmptyArtifact($user, 'F', $tracker_level_three->getId());

        $artifact_g = $this->createEmptyArtifact($user, 'G', $tracker_level_four->getId());
        $artifact_h = $this->createEmptyArtifact($user, 'H', $tracker_level_four->getId());

        Tracker_FormElementFactory::clearCaches();

        if (! $artifact_a->linkArtifact($artifact_b->getId(), $user)) {
            echo "Cannot link parent A to children B\n";
        }

        if (! $artifact_a->linkArtifact($artifact_c->getId(), $user)) {
            echo "Cannot link parent A to children  C\n";
        }

        if (! $artifact_b->linkArtifact($artifact_d->getId(), $user)) {
            echo "Cannot link parent B to children  D\n";
        }

        if (! $artifact_b->linkArtifact($artifact_e->getId(), $user)) {
            echo "Cannot link parent B to children  E\n";
        }

        if (! $artifact_b->linkArtifact($artifact_g->getId(), $user)) {
            echo "Cannot link parent B to children  G\n";
        }

        if (! $artifact_e->linkArtifact($artifact_h->getId(), $user)) {
            echo "Cannot link parent E to children  H\n";
        }

        if (! $artifact_c->linkArtifact($artifact_f->getId(), $user)) {
            echo "Cannot link parent C to children F\n";
        }

        $this->setManualValueForSlowComputedArtifact($artifact_a, $user, 'A');

        $this->setManualValueForComputedArtifact(
            $artifact_b,
            $user,
            $tracker_level_two->getId(),
            'B',
            array('is_autocomputed' => true),
            null,
            'total_effort',
            array('is_autocomputed' => true)
        );
        $this->setManualValueForComputedArtifact(
            $artifact_c,
            $user,
            $tracker_level_two->getId(),
            'C',
            array('is_autocomputed' => true),
            null,
            'total_effort',
            array('is_autocomputed' => true)
        );

        $this->setManualValueForComputedArtifact(
            $artifact_d,
            $user,
            $tracker_level_three->getId(),
            'D',
            array('manual_value' => 5),
            null,
            'effort_estimate',
            11
        );
        $this->setManualValueForComputedArtifact(
            $artifact_e,
            $user,
            $tracker_level_three->getId(),
            'E',
            array('is_autocomputed' => true),
            null,
            'effort_estimate',
            22
        );
        $this->setManualValueForComputedArtifact(
            $artifact_f,
            $user,
            $tracker_level_three->getId(),
            'F',
            array('manual_value' => 5),
            null,
            null,
            null
        );

        $this->setManualValueForComputedArtifact(
            $artifact_g,
            $user,
            $tracker_level_four->getId(),
            'G',
            5,
            15,
            null,
            null
        );
        $this->setManualValueForComputedArtifact(
            $artifact_h,
            $user,
            $tracker_level_four->getId(),
            'H',
            5,
            10,
            null,
            null
        );

        return $this;
    }

    private function createEmptyArtifact(PFUSer $user, $name, $tracker_id)
    {
        $fields_data = array(
            $this->tracker_formelement_factory->getFormElementByName($tracker_id, 'name')->getId() => $name,
            $this->tracker_formelement_factory->getFormElementByName($tracker_id, 'remaining_effort')->getId() => null,
            $this->tracker_formelement_factory->getFormElementByName($tracker_id, 'capacity')->getId() => null,
        );

        return $this->tracker_artifact_factory->createArtifact(
            $this->tracker_factory->getTrackerById($tracker_id),
            $fields_data,
            $user,
            '',
            false
        );
    }

    private function setManualValueForComputedArtifact(
        Tracker_Artifact $artifact,
        PFUser $user,
        $tracker_id,
        $field_artifact_name,
        $remaining_effort,
        $capacity,
        $field_name,
        $field_value
    ) {
        $fields_data = array(
            $this->tracker_formelement_factory->getFormElementByName($tracker_id, 'remaining_effort')->getId() => $remaining_effort,
            $this->tracker_formelement_factory->getFormElementByName($tracker_id, 'capacity')->getId()         => $capacity,
            $this->tracker_formelement_factory->getFormElementByName($tracker_id, 'name')->getId()             => $field_artifact_name
        );

        if ($field_name !== null) {
            $fields_data[$this->tracker_formelement_factory->getFormElementByName($tracker_id, $field_name)->getId()] = $field_value;
        }

        $artifact->createNewChangeset($fields_data, '', $user, false);
    }

    private function getValueDao()
    {
        return new Tracker_FormElement_Field_ComputedDao();
    }

    private function setManualValueForSlowComputedArtifact(Tracker_Artifact $artifact, PFUser $user, $field_artifact_name)
    {
        $tracker_level_one    = $this->getLevelOneTracker();
        $tracker_level_one_id = $tracker_level_one->getId();

        $field = $this->tracker_formelement_factory->getFormElementByName($tracker_level_one_id, 'progress');
        $dar = $this->getValueDao()->searchByFieldId($field->getId());
        if ($dar && count($dar)) {
            $row = $dar->getRow();
        }

        $fields_data = array(
            $field->getId() => null,
            $this->tracker_formelement_factory->getFormElementByName($tracker_level_one_id, 'remaining_effort')->getId() => null,
            $this->tracker_formelement_factory->getFormElementByName($tracker_level_one_id, 'capacity')->getId() => null,
            $this->tracker_formelement_factory->getFormElementByName($tracker_level_one_id, 'name')->getId() => $field_artifact_name
        );

        $row['target_field_name'] = 'remaining_effort';
        $row['fast_compute'] = 0;
        $this->getValueDao()->save($field->getId(), $row);

        $artifact->createNewChangeset($fields_data, '', $user, false);
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
            new NatureDao()
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

        $this->tracker_formelement_factory->clearInstance();
        $this->tracker_formelement_factory->instance();

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

    private function clearFormElementCache() {
        $this->tracker_formelement_factory->clearInstance();
        $this->tracker_formelement_factory->instance();
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

    /**
     * @return Tracker
     */
    private function getLevelOneTracker()
    {
        return $this->getTrackerInProjectComputedFields(self::LEVEL_ONE_TRACKER_SHORTNAME);
    }

    /**
     * @return Tracker
     */
    private function getLevelTwoTracker()
    {
        return $this->getTrackerInProjectComputedFields(self::LEVEL_TWO_TRACKER_SHORTNAME);
    }

    /**
     * @return Tracker
     */
    private function getLevelThreeTracker()
    {
        return $this->getTrackerInProjectComputedFields(self::LEVEL_THREE_TRACKER_SHORTNAME);
    }

    /**
     * @return Tracker
     */
    private function getLevelFourTracker()
    {
        return $this->getTrackerInProjectComputedFields(self::LEVEL_FOUR_TRACKER_SHORTNAME);
    }

    private function getTrackerInProjectComputedFields($tracker_shortname)
    {
        return $this->getTrackerInProject($tracker_shortname, self::PROJECT_COMPUTED_FIELDS);
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

        $this->tracker_artifact_factory->createArtifact(
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

        $this->tracker_artifact_factory->createArtifact(
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

        $this->tracker_artifact_factory->createArtifact(
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

        $this->tracker_artifact_factory->createArtifact(
            $tracker,
            $fields_data,
            $user,
            '',
            false
        );
    }
}
