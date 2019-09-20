<?php
/**
 * Copyright (c) Enalean, 2014. All Rights Reserved.
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

require_once dirname(__FILE__).'/GerritTestBase.php';

class Git_Driver_GerritLegacy_projectExistsTest extends TuleapTestCase implements Git_Driver_Gerrit_projectExistsTest
{
    public function setUp()
    {
        parent::setUp();
        $this->ls_project_return = array(
            'All-Projects',
            'project',
        );

        $this->gerrit_driver = partial_mock('Git_Driver_GerritLegacy', array('listParentProjects'));
        stub($this->gerrit_driver)->listParentProjects()->returns($this->ls_project_return);

        $this->gerrit_server = mock('Git_RemoteServer_GerritServer');
    }

    public function itReturnsTrueIfParentProjectExists()
    {
        $this->assertTrue($this->gerrit_driver->doesTheParentProjectExist($this->gerrit_server, 'project'));
    }

    public function itReturnsFalseIfParentProjectDoNotExists()
    {
        $this->assertFalse($this->gerrit_driver->doesTheParentProjectExist($this->gerrit_server, 'project_not_existing'));
    }
}

class Git_Driver_Gerrit_Legacy_LsParentProjectsTest extends TuleapTestCase
{

    public function setUp()
    {
        parent::setUp();
        $this->gerrit_server = mock('Git_RemoteServer_GerritServer');

        $this->ssh    = mock('Git_Driver_Gerrit_RemoteSSHCommand');
        $this->logger = mock('BackendLogger');
        $this->driver = new Git_Driver_GerritLegacy($this->ssh, $this->logger);
    }

    public function itUsesGerritSSHCommandToListParentProjects()
    {
        expect($this->ssh)->execute($this->gerrit_server, 'gerrit ls-projects --type PERMISSIONS')->once();
        $this->driver->listParentProjects($this->gerrit_server);
    }

    public function itReturnsAllPlatformParentProjects()
    {
        $ls_projects_expected_return = array(
            'project',
            'project/project_members',
            'project/project_admins',
            'project/group_from_ldap',
        );

        $ssh_ls_projects = 'project
project/project_members
project/project_admins
project/group_from_ldap';

        stub($this->ssh)->execute()->returns($ssh_ls_projects);

        $this->assertEqual(
            $ls_projects_expected_return,
            $this->driver->listParentProjects($this->gerrit_server)
        );
    }
}
