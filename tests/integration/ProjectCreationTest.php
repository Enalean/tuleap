<?php

/**
 * Copyright (c) Enalean, 2012 - 2017. All Rights Reserved.
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

use Tuleap\Project\UgroupDuplicator;
use Tuleap\FRS\FRSPermissionCreator;
use Tuleap\FRS\FRSPermissionDao;
use Tuleap\Dashboard\Project\ProjectDashboardDuplicator;
use Tuleap\Dashboard\Project\ProjectDashboardDao;
use Tuleap\Dashboard\Widget\DashboardWidgetDao;
use Tuleap\Dashboard\Project\ProjectDashboardRetriever;
use Tuleap\Dashboard\Widget\DashboardWidgetRetriever;
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
        $GLOBALS['sys_force_ssl'] = 0;
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
        unset($GLOBALS['sys_force_ssl']);
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

        $force_activation = false;

        $projectCreator = new ProjectCreator(
            ProjectManager::instance(),
            ReferenceManager::instance(),
            $ugroup_duplicator,
            $send_notifications,
            new FRSPermissionCreator(
                new FRSPermissionDao(),
                new UGroupDao()
            ),
            $duplicator,
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
