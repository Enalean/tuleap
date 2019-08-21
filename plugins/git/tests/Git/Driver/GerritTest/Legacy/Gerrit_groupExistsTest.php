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

class Git_Driver_Gerrit_Legacy_GroupExistsTest extends TuleapTestCase implements Git_Driver_Gerrit_groupExistsTest{

    public function setUp()
    {
        parent::setUp();
        $this->ls_group_return = array(
            'Administrators',
            'Anonymous Users',
            'Non-Interactive Users',
            'Project Owners',
            'Registered Users',
            'project/project_members',
            'project/project_admins',
            'project/group_from_ldap',
        );

        $this->gerrit_driver = partial_mock('Git_Driver_GerritLegacy', array('listGroups'));
        stub($this->gerrit_driver)->listGroups()->returns($this->ls_group_return);

        $this->gerrit_server = mock('Git_RemoteServer_GerritServer');
    }

    public function itCallsLsGroups()
    {
        expect($this->gerrit_driver)->listGroups($this->gerrit_server)->once();
        $this->gerrit_driver->doesTheGroupExist($this->gerrit_server, 'whatever');
    }

    public function itReturnsTrueIfGroupExists()
    {
        $this->assertTrue($this->gerrit_driver->doesTheGroupExist($this->gerrit_server, 'project/project_admins'));
    }

    public function itReturnsFalseIfGroupDoNotExists()
    {
        $this->assertFalse($this->gerrit_driver->doesTheGroupExist($this->gerrit_server, 'project/wiki_admins'));
    }
}
