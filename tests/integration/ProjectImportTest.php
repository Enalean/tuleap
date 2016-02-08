<?php
/**
 * Copyright (c) Enalean, 2012. All Rights Reserved.
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

require_once 'exit.php';
require_once 'html.php';
require_once 'user.php';

class ProjectImportTest_SystemEventRunner extends Tuleap\Project\SystemEventRunner {

}

class ProjectImportTest extends TuleapDbTestCase {

    public function __construct() {
        parent::__construct();

        // Uncomment this during development to avoid aweful 50" setUp
        // $this->markThisTestUnderDevelopment();
    }

    public function setUp() {
        parent::setUp();
        PluginManager::instance()->invalidateCache();
        PluginFactory::clearInstance();
        $this->old_globals = $GLOBALS;
        $GLOBALS['feedback'] = '';
        $GLOBALS['svn_prefix'] = '/tmp';
        $GLOBALS['cvs_prefix'] = '/tmp';
        $GLOBALS['grpdir_prefix'] = '/tmp';
        $GLOBALS['ftp_frs_dir_prefix'] = '/tmp';
        $GLOBALS['ftp_anon_dir_prefix'] = '/tmp';
        $GLOBALS['sys_default_domain'] = '';
        $GLOBALS['sys_cookie_prefix'] = '';
        $GLOBALS['sys_force_ssl'] = 0;
        ForgeConfig::store();
        $this->old_sys_pluginsroot = $GLOBALS['sys_pluginsroot'];
        $this->old_sys_custompluginsroot = $GLOBALS['sys_custompluginsroot'];
        $GLOBALS['sys_pluginsroot'] = dirname(__FILE__) . '/../../plugins/';
        $GLOBALS['sys_custompluginsroot'] = "/tmp";
        ForgeConfig::set('tuleap_dir', __DIR__.'/../../');
        ForgeConfig::set('codendi_log', "/tmp/");
        /**
         * HACK
         */
        require_once dirname(__FILE__).'/../../plugins/fusionforge_compat/include/fusionforge_compatPlugin.class.php';
        $ff_plugin = new fusionforge_compatPlugin();
        $ff_plugin->loaded();

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

        putenv('TULEAP_LOCAL_INC='.dirname(__FILE__).'/_fixtures/local.inc');
    }

    public function tearDown() {
        ForgeConfig::restore();
        $this->mysqli->query('DELETE FROM groups WHERE unix_group_name = "short-name"');
        unset($GLOBALS['svn_prefix']);
        unset($GLOBALS['cvs_prefix']);
        unset($GLOBALS['grpdir_prefix']);
        unset($GLOBALS['ftp_frs_dir_prefix']);
        unset($GLOBALS['ftp_anon_dir_prefix']);
        unset($GLOBALS['sys_default_domain']);
        unset($GLOBALS['sys_cookie_prefix']);
        unset($GLOBALS['sys_force_ssl']);
        $GLOBALS['sys_pluginsroot'] = $this->old_sys_pluginsroot;
        $GLOBALS['sys_custompluginsroot'] = $this->old_sys_custompluginsroot;
        EventManager::clearInstance();
        PluginManager::instance()->invalidateCache();
        PluginFactory::clearInstance();
        $GLOBALS = $this->old_globals;
        parent::tearDown();
    }

    public function testImportProjectCreatesAProject() {
        $project_manager = ProjectManager::instance();
        $user_manager = UserManager::instance();
        $importer = new ProjectXMLImporter(
            EventManager::instance(),
            $project_manager,
            new XML_RNGValidator(),
            new UGroupManager(),
            new XMLImportHelper($user_manager),
            new Log_ConsoleLogger()
        );
        $system_event_runner = mock('ProjectImportTest_SystemEventRunner');
        $archive = new Tuleap\Project\XML\Import\DirectoryArchive(__DIR__.'/_fixtures/fake_project');

        $importer->importNewFromArchive($archive, $system_event_runner);

        // Reset Project Manager (and its cache)
        ProjectManager::clearInstance();
        $project_manager = ProjectManager::instance();

        // Check the project was created
        $project = $project_manager->getProjectByUnixName('toto123');
        $this->assertEqual($project->getPublicName(), 'Toto 123');
        $this->assertEqual($project->getDescription(), '123 Soleil');
        $this->assertEqual($project->usesSVN(), true);
        $this->assertEqual($project->usesCVS(), false);
        $this->assertEqual($project->usesService('plugin_mediawiki'), true);
        $system_event_runner->expectCallCount('runSystemEvents', 1);
        $system_event_runner->expectCallCount('checkPermissions', 1);

        //Mediawiki import tests
        $mediawiki_dao = new MediawikiDao();
        $nb_pages = $mediawiki_dao->getMediawikiPagesNumberOfAProject($project);
        $this->assertEqual(3, $nb_pages['result']);
    }
}
