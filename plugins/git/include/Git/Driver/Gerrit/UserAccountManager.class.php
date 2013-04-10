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

    /**
     *
     * @param array $original_keys
     * @param array $new_keys
     * @param Git_RemoteServer_GerritServerFactory $remote_gerrit_factory
     * @return void
     * @throws Git_Driver_Gerrit_UserSynchronisationException
     */
    public function synchroniseSSHKeys(Array $original_keys, Array $new_keys, Git_RemoteServer_GerritServerFactory $remote_gerrit_factory) {
        $keys_to_add    = $this->getKeysToAdd($original_keys, $new_keys);
        $keys_to_remove = $this->getKeysToRemove($original_keys, $new_keys);

        if (! $this->areThereKeysToUpdate($keys_to_add, $keys_to_remove)) {
            return;
        }
        
        $errors = array();
        $remote_servers = $remote_gerrit_factory->getRemoteServersForUser($this->user);

        foreach($remote_servers as $remote_server) {
            try {
                $this->removeKeys($remote_server, $keys_to_add);
                $this->removeKeys($remote_server, $keys_to_remove);
            } catch (Git_Driver_Gerrit_RemoteSSHCommandFailure $e) {
                $errors[] = $e->getTraceAsString();
            }
        }
        //double foreach to workaround gerrit bug
        foreach($remote_servers as $remote_server) {
            try {
                $this->addKeys($remote_server, $keys_to_add);
            } catch (Git_Driver_Gerrit_RemoteSSHCommandFailure $e) {
                $errors[] = $e->getTraceAsString();
            }
        }
        
        if ($errors) {
            $message = implode(PHP_EOL, $errors);
            throw new Git_Driver_Gerrit_UserSynchronisationException($message);
        }
    }

    /**
     * Makes sure there is one copy of each key on each remote server
     * 
     * @param Git_RemoteServer_GerritServerFactory $remote_gerrit_factory
     * @return void
     * @throws Git_Driver_Gerrit_UserSynchronisationException
     */
    public function pushSSHKeys(Git_RemoteServer_GerritServerFactory $remote_gerrit_factory) {
        $user_keys = array_unique($this->user->getAuthorizedKeysArray());

        if (! $user_keys) {
            return;
        }

        $errors = array();
        $remote_servers = $remote_gerrit_factory->getRemoteServersForUser($this->user);
        
        foreach($remote_servers as $remote_server) {
           try { 
                $this->removeKeys($remote_server, $user_keys);
                $this->addKeys($remote_server, $user_keys);
            } catch (Git_Driver_Gerrit_RemoteSSHCommandFailure $e) {
                $errors[] = $e->getTraceAsString();
            }
        }

        if ($errors) {
            $message = implode(PHP_EOL, $errors);
            throw new Git_Driver_Gerrit_UserSynchronisationException($message);
        }
    }

    /**
     *
     * @param Git_RemoteServer_GerritServer $remote_server
     * @param array $keys
     * @throws Git_Driver_Gerrit_RemoteSSHCommandFailure
     */
    private function addKeys(Git_RemoteServer_GerritServer $remote_server, Array $keys) {
        foreach($keys as $key) {
            $this->gerrit_driver->addSSHKeyToAccount($remote_server, $this->user, $key);
        }
    }

    /**
     *
     * @param Git_RemoteServer_GerritServer $remote_server
     * @param array $keys
     * @throws Git_Driver_Gerrit_RemoteSSHCommandFailure
     */
    private function removeKeys(Git_RemoteServer_GerritServer $remote_server, Array $keys) {
        foreach($keys as $key) {
            $this->gerrit_driver->removeSSHKeyFromAccount($remote_server, $this->user, $key);
        }
    }

    /**
     *
     * @param array $original_keys
     * @param array $new_keys
     * @return array
     */
    private function getKeysToAdd(Array $original_keys, Array $new_keys) {
        return array_unique(array_diff($new_keys, $original_keys));
    }

    /**
     *
     * @param array $original_keys
     * @param array $new_keys
     * @return array
     */
    private function getKeysToRemove(Array $original_keys, Array $new_keys) {
        return array_unique(array_diff($original_keys, $new_keys));
    }

    /**
     *
     * @param array $keys_to_add
     * @param array $keys_to_remove
     * @return array
     */
    private function areThereKeysToUpdate(Array $keys_to_add, Array $keys_to_remove) {
        return $keys_to_add || $keys_to_remove;
    }
}
?>
