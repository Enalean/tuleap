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

require_once 'common/project/ProjectXMLImporter.class.php';
require_once 'exit.php';
require_once 'html.php';
require_once 'user.php';

class ProjectImportTest extends TuleapDbTestCase {

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

        $archive = new Tuleap\Project\XML\Import\DirectoryArchive(__DIR__.'/_fixtures/fake_project');

        $importer->importNewFromArchive($archive);

        // Reset Project Manager (and its cache)
        ProjectManager::clearInstance();
        $project_manager = ProjectManager::instance();

        // Check the project was created
        $project = $project_manager->getProjectByUnixName('toto123');
        $this->assertEqual($project->getPublicName(), 'Toto 123');
        $this->assertEqual($project->getDescription(), '123 Soleil');
        $this->assertEqual($project->usesSVN(), true);
        $this->assertEqual($project->usesCVS(), false);
    }
}
