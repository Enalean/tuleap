<?php
/**
 * Copyright (c) Enalean, 2015 - 2017. All rights reserved
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

use Tuleap\Project\UgroupDuplicator;
use Tuleap\FRS\FRSPermissionCreator;
use Tuleap\FRS\FRSPermissionDao;
use Tuleap\Dashboard\Project\ProjectDashboardDuplicator;
use Tuleap\Dashboard\Project\ProjectDashboardDao;
use Tuleap\Dashboard\Widget\DashboardWidgetDao;
use Tuleap\Dashboard\Project\ProjectDashboardRetriever;
use Tuleap\Dashboard\Widget\DashboardWidgetRetriever;
use Tuleap\Service\ServiceCreator;
use Tuleap\Widget\WidgetFactory;

class SOAP_TestDataBuilder extends TestDataBuilder {

    const TV3_SERVICE_ID      = 15;
    const TV3_TASK_REPORT_ID  = 102;

    const PROJECT_PRIVATE_MEMBER_ID = 101;

    /** @var ProjectCreator */
    private $project_creator;

    /** @var UserPermissionsDao */
    private $user_permissions_dao;

    public function __construct() {
        parent::__construct();

        include_once 'account.php';
        include_once 'www/project/admin/UserPermissionsDao.class.php';

        $this->user_permissions_dao = new UserPermissionsDao();
        $send_notifications         = true;
        $ugroup_user_dao            = new UGroupUserDao();
        $ugroup_manager             = new UGroupManager();
        $ugroup_duplicator    = new UgroupDuplicator(
            new UGroupDao(),
            $ugroup_manager,
            new UGroupBinding($ugroup_user_dao, $ugroup_manager),
            $ugroup_user_dao,
            EventManager::instance()
        );

        $widget_factory = new WidgetFactory(
            UserManager::instance(),
            new User_ForgeUserGroupPermissionsManager(new User_ForgeUserGroupPermissionsDao()),
            EventManager::instance()
        );

        $widget_dao        = new DashboardWidgetDao($widget_factory);
        $project_dao       = new ProjectDashboardDao($widget_dao);
        $project_retriever = new ProjectDashboardRetriever($project_dao);
        $widget_retriever  = new DashboardWidgetRetriever($widget_dao);
        $duplicator        = new ProjectDashboardDuplicator(
            $project_dao,
            $project_retriever,
            $widget_dao,
            $widget_retriever,
            $widget_factory
        );

        $force_activation = true;

        $this->project_creator = new ProjectCreator(
            $this->project_manager,
            ReferenceManager::instance(),
            $this->user_manager,
            $ugroup_duplicator,
            $send_notifications,
            new FRSPermissionCreator(
                new FRSPermissionDao(),
                new UGroupDao()
            ),
            $duplicator,
            new ServiceCreator(),
            $force_activation
        );
    }

    public function activatePlugins() {
        $this->activatePlugin('docman');
        PluginManager::instance()->invalidateCache();
        PluginManager::instance()->loadPlugins();

        return $this;
    }

    public function initPlugins() {
        foreach (glob(dirname(__FILE__).'/../../../plugins/*/tests/soap/init_test_data.php') as $init_file) {
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
        $this->user_manager->createAccount($user_2);
        $user_2->setLabFeatures(true);

        return $this;
    }

    public function generateProject() {
        $this->setGlobalsForProjectCreation();

        $user_test_soap = $this->user_manager->getUserByUserName(self::TEST_USER_1_NAME);

        echo "Create projects\n";

        $services = array(
            self::TV3_SERVICE_ID => array('is_used' => '1')
        );

        $project_1 = $this->createProject(
            self::PROJECT_PRIVATE_MEMBER_SHORTNAME,
            'Private member',
            false,
            array($user_test_soap),
            array($user_test_soap),
            $services
        );
        $this->addUserGroupsToProject($project_1);

        $this->unsetGlobalsForProjectCreation();

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
        array $project_admins,
        array $services
    ) {
        $first_admin = array_shift($project_admins);
        if (! $first_admin) {
            $first_admin = $this->user_manager->getUserByUserName(self::ADMIN_USER_NAME);
        }
        $this->user_manager->setCurrentUser($first_admin);

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

    private function addUserToUserGroup($user, $project, $ugroup_id) {
        ugroup_add_user_to_ugroup($project->getId(), $ugroup_id, $user->getId());
    }

    private function addUserGroupsToProject(Project $project) {
        ugroup_create($project->getId(), 'static_ugroup_1', 'static_ugroup_1', '');
        ugroup_create($project->getId(), 'static_ugroup_2', 'static_ugroup_2', '');
    }

    private function setGlobalsForProjectCreation() {
        $GLOBALS['svn_prefix'] = '/tmp';
        $GLOBALS['cvs_prefix'] = '/tmp';
        $GLOBALS['grpdir_prefix'] = '/tmp';
        $GLOBALS['ftp_frs_dir_prefix'] = '/tmp';
        $GLOBALS['ftp_anon_dir_prefix'] = '/tmp';
        $GLOBALS['codendi_bin_prefix'] = '/tmp';
    }

    private function unsetGlobalsForProjectCreation() {
        unset($GLOBALS['svn_prefix']);
        unset($GLOBALS['cvs_prefix']);
        unset($GLOBALS['grpdir_prefix']);
        unset($GLOBALS['ftp_frs_dir_prefix']);
        unset($GLOBALS['ftp_anon_dir_prefix']);
        unset($GLOBALS['codendi_bin_prefix']);
    }
}
