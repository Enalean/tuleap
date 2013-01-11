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

require_once 'common/project/ProjectCreator.class.php';
require_once 'exit.php';
require_once 'html.php';
require_once 'user.php';

class ProjectCreationTest extends TuleapDbTestCase {

    public function __construct() {
        parent::__construct();

        // Uncomment this during development to avoid aweful 50" setUp
        // $this->markThisTestUnderDevelopment()
    }

    public function setUp() {
        parent::setUp();
        $GLOBALS['feedback'] = '';
        $GLOBALS['svn_prefix'] = '/tmp';
        $GLOBALS['cvs_prefix'] = '/tmp';
    }

    public function tearDown() {
        $this->mysqli->query('DELETE FROM groups WHERE unix_group_name = "short-name"');
        unset($GLOBALS['svn_prefix']);
        unset($GLOBALS['cvs_prefix']);
        parent::tearDown();
    }

    public function itCreatesAProject() {
        $projectCreator = new ProjectCreator(ProjectManager::instance(), new Rule_ProjectName(), new Rule_ProjectFullName());
        $projectCreator->create('short-name', 'Long name', array(
            'project' => array(
                'form_license'           => 'xrx',
                'form_license_other'     => '',
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
?>
