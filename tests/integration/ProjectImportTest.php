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

use Tuleap\Dashboard\Project\ProjectDashboardRetriever;
use Tuleap\Dashboard\Widget\DashboardWidgetRetriever;
use Tuleap\Project\Label\LabelDao;
use Tuleap\Project\UgroupDuplicator;
use Tuleap\FRS\FRSPermissionCreator;
use Tuleap\FRS\FRSPermissionDao;
use Tuleap\Project\UserRemover;
use Tuleap\Dashboard\Project\ProjectDashboardDuplicator;
use Tuleap\Dashboard\Project\ProjectDashboardDao;
use Tuleap\Dashboard\Widget\DashboardWidgetDao;
use Tuleap\Service\ServiceCreator;
use Tuleap\Widget\WidgetFactory;

require_once __DIR__ . '/bootstrap.php';

class ProjectImportTest_SystemEventRunner extends Tuleap\Project\SystemEventRunner {

}

class ProjectImportTest extends TuleapDbTestCase
{

    public function __construct()
    {
        parent::__construct();

        // Uncomment this during development to avoid aweful 50" setUp
        // $this->markThisTestUnderDevelopment();
    }

    public function setUp()
    {
        parent::setUp();
        PluginManager::instance()->invalidateCache();
        PluginFactory::clearInstance();
        UserManager::clearInstance();
        $user_manager = UserManager::instance();
        $user_admin = $user_manager->getUserByUserName('admin');
        $user_manager->setCurrentUser($user_admin);
        $this->old_globals = $GLOBALS;
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
        $this->old_sys_pluginsroot = $GLOBALS['sys_pluginsroot'];
        $this->old_sys_custompluginsroot = $GLOBALS['sys_custompluginsroot'];
        $GLOBALS['sys_pluginsroot'] = dirname(__FILE__) . '/../../plugins/';
        $GLOBALS['sys_custompluginsroot'] = "/tmp";
        ForgeConfig::set('tuleap_dir', __DIR__ . '/../../');
        ForgeConfig::set('codendi_log', "/tmp/");
        ForgeConfig::set('sys_dbhost', $sys_dbhost);
        ForgeConfig::set('sys_dbuser', $sys_dbuser);
        ForgeConfig::set('sys_dbpasswd', $sys_dbpasswd);
        ForgeConfig::set('sys_dbname', $sys_dbname);

        $this->sys_command = new System_Command();

        putenv('TULEAP_LOCAL_INC=' . dirname(__FILE__) . '/_fixtures/local.inc');
    }

    public function tearDown()
    {
        ForgeConfig::restore();
        $this->mysqli->query('DELETE FROM groups WHERE unix_group_name = "short-name"');
        unset($GLOBALS['svn_prefix']);
        unset($GLOBALS['cvs_prefix']);
        unset($GLOBALS['grpdir_prefix']);
        unset($GLOBALS['ftp_frs_dir_prefix']);
        unset($GLOBALS['ftp_anon_dir_prefix']);
        unset($GLOBALS['sys_default_domain']);
        unset($GLOBALS['sys_cookie_prefix']);
        $GLOBALS['sys_pluginsroot'] = $this->old_sys_pluginsroot;
        $GLOBALS['sys_custompluginsroot'] = $this->old_sys_custompluginsroot;
        EventManager::clearInstance();
        PluginManager::instance()->invalidateCache();
        PluginFactory::clearInstance();
        UserManager::clearInstance();
        $GLOBALS = $this->old_globals;
        parent::tearDown();
    }

    public function itStopWhenNatureDontExistOnPlateform()
    {
        $ugroup_user_dao = new UGroupUserDao();
        $ugroup_manager = new UGroupManager();
        $ugroup_duplicator = new UgroupDuplicator(
            new UGroupDao(),
            $ugroup_manager,
            new UGroupBinding($ugroup_user_dao, $ugroup_manager),
            $ugroup_user_dao,
            EventManager::instance()
        );

        $project_manager = ProjectManager::instance();
        $user_manager    = UserManager::instance();

        $send_notifications = false;
        $force_activation   = true;

        $frs_permissions_creator = new FRSPermissionCreator(
            new FRSPermissionDao(),
            new UGroupDao()
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

        $project_creator = new ProjectCreator(
            $project_manager,
            ReferenceManager::instance(),
            $user_manager,
            $ugroup_duplicator,
            $send_notifications,
            $frs_permissions_creator,
            $duplicator,
            new ServiceCreator(),
            new LabelDao(),
            $force_activation
        );

        $importer = new ProjectXMLImporter(
            EventManager::instance(),
            $project_manager,
            UserManager::instance(),
            new XML_RNGValidator(),
            new UGroupManager(),
            new XMLImportHelper($user_manager),
            ServiceManager::instance(),
            new Log_ConsoleLogger(),
            $ugroup_duplicator,
            $frs_permissions_creator,
            new UserRemover(
                mock('ProjectManager'),
                mock('EventManager'),
                mock('ArtifactTypeFactory'),
                mock('Tuleap\Project\UserRemoverDao'),
                mock('UserManager'),
                mock('ProjectHistoryDao'),
                mock('UGroupManager')
            ),
            $project_creator,
            mock('Tuleap\FRS\UploadedLinksUpdater'),
            mock('Tuleap\Dashboard\Project\ProjectDashboardXMLImporter')
        );

        $system_event_runner = mock('ProjectImportTest_SystemEventRunner');
        $archive = new Tuleap\Project\XML\Import\DirectoryArchive(__DIR__ . '/_fixtures/fake_project_with_missing_natures');

        $this->expectException();
        $importer->importNewFromArchive(
            new Tuleap\Project\XML\Import\ImportConfig(),
            $archive,
            $system_event_runner,
            false
        );
    }
}
