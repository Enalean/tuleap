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

require_once 'exit.php';
require_once 'html.php';
require_once 'user.php';

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
        /**
         * HACK
         */
        require_once dirname(__FILE__) . '/../../plugins/mediawiki/fusionforge/compat/load_compatibilities_method.php';

        PluginManager::instance()->installAndActivate('mediawiki');

        $plugin = PluginManager::instance()->getPluginByName('mediawiki');
        EventManager::instance()->addListener(
            Event::IMPORT_XML_PROJECT,
            $plugin,
            'importXmlProject',
            false
        );
        EventManager::instance()->addListener(
            'register_project_creation',
            $plugin,
            'register_project_creation',
            false
        );
        EventManager::instance()->addListener(
            Event::SERVICES_ALLOWED_FOR_PROJECT,
            $plugin,
            'services_allowed_for_project',
            false
        );

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

    public function testImportProjectCreatesAProject()
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
        $archive = new Tuleap\Project\XML\Import\DirectoryArchive(__DIR__ . '/_fixtures/fake_project');

        $importer->importNewFromArchive(
            new Tuleap\Project\XML\Import\ImportConfig(),
            $archive,
            $system_event_runner,
            false
        );

        // Reset Project Manager (and its cache)
        ProjectManager::clearInstance();
        $project_manager = ProjectManager::instance();

        // Check the project was created
        $project = $project_manager->getProjectByUnixName('toto123');
        $this->assertEqual($project->getPublicName(), 'Toto 123');
        $this->assertEqual($project->getDescription(), '123 Soleil');
        $this->assertEqual($project->usesSVN(), false);
        $this->assertEqual($project->usesCVS(), false);
        $this->assertEqual($project->usesService('plugin_mediawiki'), true);
        $system_event_runner->expectCallCount('runSystemEvents', 1);
        $system_event_runner->expectCallCount('checkPermissions', 1);

        $this->mediawikiTests($project);
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

    private function mediawikiTests(Project $project) {
        $ugroup_manager        = new UGroupManager();
        $mediawiki_dao         = new MediawikiDao();
        $mediawiki_manager     = new MediawikiManager($mediawiki_dao);
        $mediawikilanguage_dao = new MediawikiLanguageDao();

        $res = $mediawiki_dao->getMediawikiPagesNumberOfAProject($project);
        $this->assertEqual(4, $res['result']);

        $res = $mediawikilanguage_dao->getUsedLanguageForProject($project->getGroupId());
        $this->assertEqual('fr_FR', $res['language']);

        $mediawiki_storage_path = forge_get_config('projects_path', 'mediawiki') . "/". $project->getID();
        $escaped_mw_st_path = escapeshellarg($mediawiki_storage_path);
        $find_cmd = "find $escaped_mw_st_path -type 'f' -iname 'tuleap.png' -printf '%f'";
        $find_res = $this->sys_command->exec($find_cmd);
        $this->assertEqual(1, count($find_res[0]));

        $owner = posix_getpwuid(fileowner($mediawiki_storage_path));
        $this->assertEqual("codendiadm", $owner['name']);
        $group = posix_getgrgid(filegroup($mediawiki_storage_path));
        $this->assertEqual("codendiadm", $group['name']);

        $project_members_id = $ugroup_manager->getUGroupByName($project, 'project_members')->getId();
        $project_admins_id  = $ugroup_manager->getUGroupByName($project, 'project_admins')->getId();

        $group_ids = $mediawiki_manager->getReadAccessControl($project);
        $this->assertEqual(array($project_members_id), $group_ids);
        $group_ids = $mediawiki_manager->getWriteAccessControl($project);
        $this->assertEqual(array($project_admins_id), $group_ids);
    }
}
