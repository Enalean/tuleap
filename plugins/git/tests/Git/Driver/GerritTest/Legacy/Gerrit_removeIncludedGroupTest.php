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

class Git_Driver_GerritLegacy_removeIncludedGroupTest extends TuleapTestCase implements Git_Driver_Gerrit_removeIncludedGroupTest
{
    public function setUp()
    {
        parent::setUp();
        $this->gerrit_server = mock('Git_RemoteServer_GerritServer');

        $this->ssh    = mock('Git_Driver_Gerrit_RemoteSSHCommand');
        $this->logger = mock('BackendLogger');
        $this->driver = partial_mock(
            'Git_Driver_GerritLegacy',
            array('getGroupId'),
            array($this->ssh, $this->logger)
        );
    }

    public function itRemovesAllIncludedGroups()
    {
        $id = 272;
        $group_name    = 'gdb/developers';

        stub($this->driver)->getGroupId($this->gerrit_server, $group_name)->returns($id);

        $delete_included_query = 'gerrit gsql --format json -c "DELETE\ FROM\ account_group_includes\ I\ WHERE\ I.group_id='.$id.'"';

        expect($this->ssh)->execute()->count(2);
        expect($this->ssh)->execute($this->gerrit_server, $delete_included_query)->at(0);
        expect($this->ssh)->execute($this->gerrit_server, 'gerrit flush-caches --cache groups_byinclude')->at(1);

        $this->driver->removeAllIncludedGroups($this->gerrit_server, $group_name);
    }
}
