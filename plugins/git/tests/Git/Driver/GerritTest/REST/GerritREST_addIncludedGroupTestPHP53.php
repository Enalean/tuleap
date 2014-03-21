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

require_once 'GerritREST_Base.php';

class Git_Driver_GerritREST_addIncludedGroupTest extends Git_Driver_GerritREST_base implements Git_Driver_Gerrit_addIncludedGroupTest {

    public function itAddAnIncludedGroup() {
        $group_name          = 'grp';
        $included_group_name = 'proj grp';

        $url = $this->gerrit_server_host
            .':'. $this->gerrit_server_port
            .'/a/groups/'.urlencode($group_name).'/groups/'.urlencode($included_group_name);

        expect($this->guzzle_client)->put(
            $url,
            array(
                'verify' => false,
            )
        )->once();
        stub($this->guzzle_client)->put()->returns($this->guzzle_request);

        $this->driver->addIncludedGroup($this->gerrit_server, $group_name, $included_group_name);
    }
}