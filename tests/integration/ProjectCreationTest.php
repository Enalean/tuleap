<?php
/**
 * Copyright (c) Enalean, 2012 - 2018. All Rights Reserved.
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

require_once 'common/project/ProjectCreator.class.php';
require_once 'exit.php';
require_once 'html.php';
require_once 'user.php';

use Tuleap\Project\Label\LabelDao;
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

class ProjectCreationTest extends TuleapDbTestCase {

    public function __construct() {
        parent::__construct();

        // Uncomment this during development to avoid aweful 50" setUp
        // $this->markThisTestUnderDevelopment();
    }

    public function setUp() {
        parent::setUp();
        $GLOBALS['feedback'] = '';
        $GLOBALS['svn_prefix'] = '/tmp';
        $GLOBALS['cvs_prefix'] = '/tmp';
        $GLOBALS['grpdir_prefix'] = '/tmp';
        $GLOBALS['ftp_frs_dir_prefix'] = '/tmp';
        $GLOBALS['ftp_anon_dir_prefix'] = '/tmp';
        $GLOBALS['sys_default_domain'] = '';
        $GLOBALS['sys_cookie_prefix'] = '';

        $sys_dbhost   = ForgeConfig::get('sys_dbhost');
        $sys_dbuser   = ForgeConfig::get('sys_dbuser');
        $sys_dbpasswd = ForgeConfig::get('sys_dbpasswd');
        $sys_dbname   = ForgeConfig::get('sys_dbname');

        ForgeConfig::store();

        ForgeConfig::set('sys_dbhost', $sys_dbhost);
        ForgeConfig::set('sys_dbuser', $sys_dbuser);
        ForgeConfig::set('sys_dbpasswd', $sys_dbpasswd);
        ForgeConfig::set('sys_dbname', $sys_dbname);
    }

    public function tearDown() {
        $this->mysqli->query('DELETE FROM groups WHERE unix_group_name = "short-name"');
        unset($GLOBALS['svn_prefix']);
        unset($GLOBALS['cvs_prefix']);
        unset($GLOBALS['grpdir_prefix']);
        unset($GLOBALS['ftp_frs_dir_prefix']);
        unset($GLOBALS['ftp_anon_dir_prefix']);
        unset($GLOBALS['sys_default_domain']);
        unset($GLOBALS['sys_cookie_prefix']);
        ForgeConfig::restore();
        parent::tearDown();
    }

    public function itCreatesAProject()
    {
        $send_notifications = true;
        $ugroup_user_dao    = new UGroupUserDao();
        $ugroup_manager     = new UGroupManager();
        $ugroup_duplicator  = new UgroupDuplicator(
            new UGroupDao(),
            $ugroup_manager,
            new UGroupBinding($ugroup_user_dao, $ugroup_manager),
            $ugroup_user_dao,
            EventManager::instance()
        );

        $user_manager   = UserManager::instance();
        $widget_factory = new WidgetFactory(
            $user_manager,
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

        $force_activation = false;

        ForgeConfig::set('sys_use_project_registration', 1);

        $projectCreator = new ProjectCreator(
            ProjectManager::instance(),
            ReferenceManager::instance(),
            $user_manager,
            $ugroup_duplicator,
            $send_notifications,
            new FRSPermissionCreator(
                new FRSPermissionDao(),
                new UGroupDao()
            ),
            $duplicator,
            new ServiceCreator(),
            new LabelDao(),
            $force_activation
        );

        $projectCreator->create('short-name', 'Long name', array(
            'project' => array(
                'form_short_description' => '',
                'is_test'                => false,
                'is_public'              => false,
                'services'               => array(),
                'built_from_template'    => 100,
            )
        ));

        ProjectManager::clearInstance();
        $project = ProjectManager::instance()->getProjectByUnixName('short-name');
        $this->assertEqual($project->getPublicName(), 'Long name');
    }
}
