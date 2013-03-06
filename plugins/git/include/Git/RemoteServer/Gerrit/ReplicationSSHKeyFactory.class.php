<?php
/**
 * Copyright (c) Enalean, 2013. All rights reserved
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/
 */

require_once 'ReplicationSSHKey.class.php';
class Git_RemoteServer_Gerrit_ReplicationSSHKeyFactory {

    /**
     *
     * @param int $id
     * @return \Git_RemoteServer_Gerrit_ReplicationSSHKey
     */
    public function fetchForGerritServerId($id) {
        return new Git_RemoteServer_Gerrit_ReplicationSSHKey();
    }


    public function save(Git_RemoteServer_Gerrit_ReplicationSSHKey $key, $gitolite_admin_path = null) {
//        if (! $gitolite_admin_path) {
//            $gitolite_admin_path = $GLOBALS['sys_data_dir'] . '/gitolite/admin';
//        }

        return $this;
    }
}
?>
