<?php
/**
 * Copyright (c) Enalean, 2013. All Rights Reserved.
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

require_once GIT_BASE_DIR .'/Git.class.php';
require_once GIT_BASE_DIR .'/Git/Driver/Gerrit/InvalidLDAPUserException.class.php';

/**
 * Encapsulate the orchestration between PermissionsManager and UgroupManager
 */
class Git_Driver_Gerrit_UserAccountManager {

    /**
     * @var Git_User
     */
    private $user;

    /**
     * @var Git_Driver_Gerrit
     */
    private $gerrit_driver;

    public function __construct(PFUser $user, Git_Driver_Gerrit $gerrit_driver) {
        if (! $user->isLDAP()) {
            throw new Git_Driver_Gerrit_InvalidLDAPUserException();
        }

        $this->user          = $user;
        $this->gerrit_driver = $gerrit_driver;
    }

    public function synchroniseSSHKeys(Array $original_keys, Array $new_keys, Git_RemoteServer_GerritServerFactory $remote_gerrit_factory) {
        if (! $this->areKeySetsDifferent($original_keys, $new_keys)) {
            return;
        }

        $remote_servers = $remote_gerrit_factory->getRemoteServersForUser($this->user);
        $keys_to_add    = $this->getKeysToAdd($original_keys, $new_keys);
        $keys_to_remove = $this->getKeysToRemove($original_keys, $new_keys);

        foreach($remote_servers as $remote_server) {
            $this->addKeysToUserForServer($remote_server, $keys_to_add);
            $this->removeKeysToUserForServer($remote_server, $keys_to_remove);
        }
    }

    private function addKeysToUserForServer(Git_RemoteServer_GerritServer $remote_server, Array $keys) {
        foreach($keys as $key) {
            $this->gerrit_driver->addSSHKeyToAccount($remote_server, $this->user, $key);
        }
    }

    private function removeKeysToUserForServer(Git_RemoteServer_GerritServer $remote_server, Array $keys) {
        foreach($keys as $key) {
            $this->gerrit_driver->removeSSHKeyFromAccount($remote_server, $this->user, $key);
        }
    }

    private function getKeysToAdd(Array $original_keys, Array $new_keys) {
        return array_diff($new_keys, $original_keys);
    }

    private function getKeysToRemove(Array $original_keys, Array $new_keys) {
        return array_diff($original_keys, $new_keys);
    }

    private function areKeySetsDifferent(Array $original_keys, Array $new_keys) {
        if (count($original_keys) != count($new_keys)) {
            return true;
        }

        $diff1 = array_diff($original_keys, $new_keys);
        if ($diff1) {
            return true;
        }

        $diff2 = array_diff($new_keys, $original_keys);
        if ($diff2) {
            return true;
        }

        return false;
    }
}
?>
