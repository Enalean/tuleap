<?php
/**
 * Copyright (c) Enalean, 2012 - 2017. All Rights Reserved.
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
 * along with Tuleap; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

require_once 'common/project/ProjectCreator.class.php';

class ProjectCreatorTest extends TuleapTestCase
{

    public function setUp()
    {
        parent::setUp();

        $GLOBALS['ftp_frs_dir_prefix'] = dirname(__FILE__) . '/_fixtures';
        $GLOBALS['svn_prefix']         = 'whatever';

        $this->event_manager = stub('SystemEventManager')->isUserNameAvailable()->returns(true);
        stub($this->event_manager)->isProjectNameAvailable()->returns(true);
        SystemEventManager::setInstance($this->event_manager);

        $this->project_manager = stub('ProjectManager')->getProjectByUnixName()->returns(null);
        ProjectManager::setInstance($this->project_manager);

        $this->user_manager = stub('UserManager')->getUserByUserName()->returns(null);
        UserManager::setInstance($this->user_manager);

        $backend_cvs = stub('BackendCVS')->isNameAvailable()->returns(true);
        BackendCVS::setInstance('CVS', $backend_cvs);

        $this->creator = partial_mock(
            'ProjectCreator',
            array('createProject'),
            array(
                $this->project_manager,
                mock('ReferenceManager'),
                mock('Tuleap\Project\UgroupDuplicator'),
                false,
                mock('Tuleap\FRS\FRSPermissionCreator')
            ));
    }

    public function tearDown()
    {
        BackendCVS::clearInstances();
        UserManager::clearInstance();
        ProjectManager::clearInstance();
        SystemEventManager::clearInstance();
        unset($GLOBALS['Language']);
        unset($GLOBALS['svn_prefix']);

        parent::tearDown();
    }

    public function testInvalidShortNameShouldRaiseException()
    {
        $this->expectException('Project_InvalidShortName_Exception');
        $this->creator->create('contains.point', 'sdf', array());
    }

    public function testInvalidFullNameShouldRaiseException()
    {
        $this->expectException('Project_InvalidFullName_Exception');
        $this->creator->create('shortname', 'a', array());
    }

    public function testCreationFailureShouldRaiseException()
    {
        $this->expectException('Project_Creation_Exception');
        $this->creator->create('shortname', 'Valid Full Name', array());
    }
}
