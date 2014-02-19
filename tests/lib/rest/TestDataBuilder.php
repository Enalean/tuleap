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

    const ADMIN_USER_NAME  = 'admin';
    const ADMIN_USER_PASS  = 'siteadmin';

    const TEST_USER_NAME   = 'rest_api_tester';
    const TEST_USER_PASS   = 'welcome0';

    const ADMIN_PROJECT_ID          = 100;
    const PROJECT_PRIVATE_MEMBER_ID = 101;
    const PROJECT_PRIVATE_ID        = 102;
    const PROJECT_PUBLIC_ID         = 103;
    const PROJECT_PUBLIC_MEMBER_ID  = 104;

    const EPICS_TRACKER_ID        = 1;
    const RELEASES_TRACKER_ID     = 2;
    const SPRINTS_TRACKER_ID      = 3;
    const TASKS_TRACKER_ID        = 4;
    const USER_STORIES_TRACKER_ID = 5;

    const RELEASE_ARTIFACT_ID  = 1;
    const SPRINT_ARTIFACT_ID   = 2;
    const EPIC_1_ARTIFACT_ID   = 3;
    const EPIC_2_ARTIFACT_ID   = 4;
    const EPIC_3_ARTIFACT_ID   = 5;
    const EPIC_4_ARTIFACT_ID   = 6;
    const STORY_1_ARTIFACT_ID  = 7;
    const STORY_2_ARTIFACT_ID  = 8;
    const STORY_3_ARTIFACT_ID  = 9;
    const STORY_4_ARTIFACT_ID  = 10;
    const STORY_5_ARTIFACT_ID  = 11;

    /** @var ProjectManager */
    private $project_manager;

    /** @var UserManager */
    private $user_manager;

    /** @var ProjectCreator */
    private $project_creator;

    /** @var UserPermissionsDao */
    private $user_permissions_dao;

    public function __construct() {
        $this->project_manager = ProjectManager::instance();
        $this->user_manager    = UserManager::instance();
        $this->project_creator = new ProjectCreator(
            $this->project_manager,
            new Rule_ProjectName(),
            new Rule_ProjectFullName()
        );
        $this->user_permissions_dao = new UserPermissionsDao();

        $GLOBALS['Language'] = new BaseLanguage('en_US', 'en_US');
    }

    public function activatePlugins() {
        $this->activatePlugin('tracker');
        $this->activatePlugin('agiledashboard');
        $this->activatePlugin('cardwall');
        PluginManager::instance()->loadPlugins();
        return $this;
    }

    private function activatePlugin($name) {
        $plugin_factory = PluginFactory::instance();
        $plugin = $plugin_factory->createPlugin($name);
        $plugin_factory->availablePlugin($plugin);
    }

    public function activateDebug() {
	Config::set('DEBUG_MODE', 1);
        return $this;
    }

    public function generateUser() {
        $user = new PFUser();
        $user->setUserName(self::TEST_USER_NAME);
        $user->setPassword(self::TEST_USER_PASS);
        $user->setUserName(self::TEST_USER_NAME);
        $user->setLanguage($GLOBALS['Language']);

        $this->user_manager->createAccount($user);

        return $this;
    }

    public function generateProject() {
        $GLOBALS['svn_prefix'] = '/tmp';
        $GLOBALS['cvs_prefix'] = '/tmp';
        $GLOBALS['grpdir_prefix'] = '/tmp';
        $GLOBALS['ftp_frs_dir_prefix'] = '/tmp';
        $GLOBALS['ftp_anon_dir_prefix'] = '/tmp';

        $user_test_rest = $this->user_manager->getUserByUserName(self::TEST_USER_NAME);

        echo "Create projects\n";

        $this->createProject('private-member', 'Private member', false, array($user_test_rest), array($user_test_rest));
        $this->createProject('private', 'Private', false, array(), array());
        $this->createProject('public', 'Public', true, array(), array());
        $this->createProject('public-member', 'Public member', true, array($user_test_rest), array());

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
    private function createProject(
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
        $this->addUserGroupsToProject($project);

        foreach ($project_members as $project_member) {
            $this->addMembersToProject($project, $project_member);
        }

        foreach ($project_admins as $project_admin) {
            $this->addAdminToProject($project, $project_admin);
        }
    }

    private function addMembersToProject(Project $project, PFUser $user) {
        account_add_user_to_group($project->getId(), $user->getUnixName());
        UserManager::clearInstance();
        $this->user_manager = UserManager::instance();
    }

    private function addAdminToProject(Project $project, PFUser $user) {
       $this->user_permissions_dao->addUserAsProjectAdmin($project, $user);
    }

    private function addUserGroupsToProject(Project $project) {
        ugroup_create($project->getId(), 'static_ugroup', 'static_ugroup', '');
    }

    public function importAgileTemplate() {
        echo "Create import XML\n";

        $xml_importer = new ProjectXMLImporter(
            EventManager::instance(),
            $this->project_manager
        );
        $this->user_manager->forceLogin(self::ADMIN_USER_NAME);
        $xml_importer->import(101, dirname(__FILE__).'/../../rest/_fixtures/tuleap_agiledashboard_template.xml');

        return $this;
    }

    public function generateMilestones() {
        echo "Create milestones\n";

        $user = $this->user_manager->getUserByUserName(self::ADMIN_USER_NAME);

        $fields_data = array(
            Tracker_FormElementFactory::instance()->getFormElementByName(self::RELEASES_TRACKER_ID, 'name')->getId() => 'Release 1.0',
            Tracker_FormElementFactory::instance()->getFormElementByName(self::RELEASES_TRACKER_ID, 'status')->getId()  => '126'
        );
        Tracker_ArtifactFactory::instance()->createArtifact(TrackerFactory::instance()->getTrackerById(self::RELEASES_TRACKER_ID), $fields_data, $user, '', false);

        $fields_data = array(
            Tracker_FormElementFactory::instance()->getFormElementByName(self::SPRINTS_TRACKER_ID, 'name')->getId()       => 'Sprint A',
            Tracker_FormElementFactory::instance()->getFormElementByName(self::SPRINTS_TRACKER_ID, 'status')->getId()     => '150',
            Tracker_FormElementFactory::instance()->getFormElementByName(self::SPRINTS_TRACKER_ID, 'start_date')->getId() => '2014-1-9',
            Tracker_FormElementFactory::instance()->getFormElementByName(self::SPRINTS_TRACKER_ID, 'duration')->getId()   => '10',
            Tracker_FormElementFactory::instance()->getFormElementByName(self::SPRINTS_TRACKER_ID, 'capacity')->getId()   => '29',
        );
        Tracker_ArtifactFactory::instance()->createArtifact(TrackerFactory::instance()->getTrackerById(self::SPRINTS_TRACKER_ID), $fields_data, $user, '', false);

        $release = Tracker_ArtifactFactory::instance()->getArtifactById(self::RELEASE_ARTIFACT_ID);
        $release->linkArtifact(self::SPRINT_ARTIFACT_ID, $user);

        return $this;
    }

    public function generateContentItems() {
        echo "Create content items\n";

        $user = $this->user_manager->getUserByUserName(self::ADMIN_USER_NAME);

        $fields_data = array(
            Tracker_FormElementFactory::instance()->getFormElementByName(self::EPICS_TRACKER_ID, 'summary_11')->getId() => 'First epic',
            Tracker_FormElementFactory::instance()->getFormElementByName(self::EPICS_TRACKER_ID, 'status')->getId()  => '101'
        );
        Tracker_ArtifactFactory::instance()->createArtifact(TrackerFactory::instance()->getTrackerById(self::EPICS_TRACKER_ID), $fields_data, $user, '', false);

        $fields_data = array(
            Tracker_FormElementFactory::instance()->getFormElementByName(self::EPICS_TRACKER_ID, 'summary_11')->getId() => 'Second epic',
            Tracker_FormElementFactory::instance()->getFormElementByName(self::EPICS_TRACKER_ID, 'status')->getId()  => '102'
        );
        Tracker_ArtifactFactory::instance()->createArtifact(TrackerFactory::instance()->getTrackerById(self::EPICS_TRACKER_ID), $fields_data, $user, '', false);

        $fields_data = array(
            Tracker_FormElementFactory::instance()->getFormElementByName(self::EPICS_TRACKER_ID, 'summary_11')->getId() => 'Third epic',
            Tracker_FormElementFactory::instance()->getFormElementByName(self::EPICS_TRACKER_ID, 'status')->getId()  => '103'
        );
        Tracker_ArtifactFactory::instance()->createArtifact(TrackerFactory::instance()->getTrackerById(self::EPICS_TRACKER_ID), $fields_data, $user, '', false);

        $fields_data = array(
            Tracker_FormElementFactory::instance()->getFormElementByName(self::EPICS_TRACKER_ID, 'summary_11')->getId() => 'Fourth epic',
            Tracker_FormElementFactory::instance()->getFormElementByName(self::EPICS_TRACKER_ID, 'status')->getId()  => '101'
        );
        Tracker_ArtifactFactory::instance()->createArtifact(TrackerFactory::instance()->getTrackerById(self::EPICS_TRACKER_ID), $fields_data, $user, '', false);

        $release = Tracker_ArtifactFactory::instance()->getArtifactById(self::RELEASE_ARTIFACT_ID);
        $release->linkArtifact(self::EPIC_1_ARTIFACT_ID, $user);
        $release->linkArtifact(self::EPIC_2_ARTIFACT_ID, $user);
        $release->linkArtifact(self::EPIC_3_ARTIFACT_ID, $user);
        $release->linkArtifact(self::EPIC_4_ARTIFACT_ID, $user);

        return $this;
    }

    public function generateBacklogItems() {
        echo "Create backlog items\n";

        $user = $this->user_manager->getUserByUserName(self::ADMIN_USER_NAME);

        $fields_data = array(
            Tracker_FormElementFactory::instance()->getFormElementByName(self::USER_STORIES_TRACKER_ID, 'i_want_to')->getId() => 'Believe',
            Tracker_FormElementFactory::instance()->getFormElementByName(self::USER_STORIES_TRACKER_ID, 'status')->getId()  => '206'
        );
        Tracker_ArtifactFactory::instance()->createArtifact(TrackerFactory::instance()->getTrackerById(self::USER_STORIES_TRACKER_ID), $fields_data, $user, '', false);

        $fields_data = array(
            Tracker_FormElementFactory::instance()->getFormElementByName(self::USER_STORIES_TRACKER_ID, 'i_want_to')->getId() => 'Break Free',
            Tracker_FormElementFactory::instance()->getFormElementByName(self::USER_STORIES_TRACKER_ID, 'status')->getId()  => '205'
        );
        Tracker_ArtifactFactory::instance()->createArtifact(TrackerFactory::instance()->getTrackerById(self::USER_STORIES_TRACKER_ID), $fields_data, $user, '', false);

        $fields_data = array(
            Tracker_FormElementFactory::instance()->getFormElementByName(self::USER_STORIES_TRACKER_ID, 'i_want_to')->getId() => 'Hughhhhhhh',
            Tracker_FormElementFactory::instance()->getFormElementByName(self::USER_STORIES_TRACKER_ID, 'status')->getId()  => '205'
        );
        Tracker_ArtifactFactory::instance()->createArtifact(TrackerFactory::instance()->getTrackerById(self::USER_STORIES_TRACKER_ID), $fields_data, $user, '', false);

        $fields_data = array(
            Tracker_FormElementFactory::instance()->getFormElementByName(self::USER_STORIES_TRACKER_ID, 'i_want_to')->getId() => 'Kill you',
            Tracker_FormElementFactory::instance()->getFormElementByName(self::USER_STORIES_TRACKER_ID, 'status')->getId()  => '205'
        );
        Tracker_ArtifactFactory::instance()->createArtifact(TrackerFactory::instance()->getTrackerById(self::USER_STORIES_TRACKER_ID), $fields_data, $user, '', false);

        $fields_data = array(
            Tracker_FormElementFactory::instance()->getFormElementByName(self::USER_STORIES_TRACKER_ID, 'i_want_to')->getId() => 'Back',
            Tracker_FormElementFactory::instance()->getFormElementByName(self::USER_STORIES_TRACKER_ID, 'status')->getId()  => '205'
        );
        Tracker_ArtifactFactory::instance()->createArtifact(TrackerFactory::instance()->getTrackerById(self::USER_STORIES_TRACKER_ID), $fields_data, $user, '', false);

        $release = Tracker_ArtifactFactory::instance()->getArtifactById(self::RELEASE_ARTIFACT_ID);
        $release->linkArtifact(self::STORY_1_ARTIFACT_ID, $user);
        $release->linkArtifact(self::STORY_2_ARTIFACT_ID, $user);
        $release->linkArtifact(self::STORY_3_ARTIFACT_ID, $user);
        $release->linkArtifact(self::STORY_4_ARTIFACT_ID, $user);
        $release->linkArtifact(self::STORY_5_ARTIFACT_ID, $user);

        $sprint = Tracker_ArtifactFactory::instance()->getArtifactById(self::SPRINT_ARTIFACT_ID);
        $sprint->linkArtifact(self::STORY_1_ARTIFACT_ID, $user);
        $sprint->linkArtifact(self::STORY_2_ARTIFACT_ID, $user);

        return $this;
    }

    public function generateTopBacklogItems() {
        echo "Create top backlog items\n";

        $user = $this->user_manager->getUserByUserName(self::ADMIN_USER_NAME);

        $fields_data = array(
            Tracker_FormElementFactory::instance()->getFormElementByName(self::EPICS_TRACKER_ID, 'summary_11')->getId() => 'Epic pic',
            Tracker_FormElementFactory::instance()->getFormElementByName(self::EPICS_TRACKER_ID, 'status')->getId()  => '101'
        );
        Tracker_ArtifactFactory::instance()->createArtifact(TrackerFactory::instance()->getTrackerById(self::EPICS_TRACKER_ID), $fields_data, $user, '', false);

        $fields_data = array(
            Tracker_FormElementFactory::instance()->getFormElementByName(self::EPICS_TRACKER_ID, 'summary_11')->getId() => "Epic c'est tout",
            Tracker_FormElementFactory::instance()->getFormElementByName(self::EPICS_TRACKER_ID, 'status')->getId()  => '101'
        );
        Tracker_ArtifactFactory::instance()->createArtifact(TrackerFactory::instance()->getTrackerById(self::EPICS_TRACKER_ID), $fields_data, $user, '', false);

        $fields_data = array(
            Tracker_FormElementFactory::instance()->getFormElementByName(self::EPICS_TRACKER_ID, 'summary_11')->getId() => 'Epic epoc',
            Tracker_FormElementFactory::instance()->getFormElementByName(self::EPICS_TRACKER_ID, 'status')->getId()  => '101'
        );
        Tracker_ArtifactFactory::instance()->createArtifact(TrackerFactory::instance()->getTrackerById(self::EPICS_TRACKER_ID), $fields_data, $user, '', false);

        return $this;
    }

}