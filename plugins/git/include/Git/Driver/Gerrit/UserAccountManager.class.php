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
     * @var Git_Driver_Gerrit
     */
    private $gerrit_driver;

    /**
     * @var Git_RemoteServer_GerritServerFactory
     */
    private $remote_gerrit_factory;

    public function __construct(Git_Driver_Gerrit $gerrit_driver, Git_RemoteServer_GerritServerFactory $remote_gerrit_factory) {
        $this->gerrit_driver = $gerrit_driver;
        $this->remote_gerrit_factory = $remote_gerrit_factory;
    }

    /**
     *
     * @param array $original_keys
     * @param array $new_keys
     * @param Git_RemoteServer_GerritServerFactory $remote_gerrit_factory
     * @return void
     * @throws Git_UserSynchronisationException
     */
    public function synchroniseSSHKeys(array $original_keys, array $new_keys, PFUser $user) {
        if (! $user->isLDAP()) {
            return;
        }

        $keys_to_add    = $this->getKeysToAdd($original_keys, $new_keys);
        $keys_to_remove = $this->getKeysToRemove($original_keys, $new_keys);

        if (! $this->areThereKeysToUpdate($keys_to_add, $keys_to_remove)) {
            return;
        }
        
        $errors = array();
        $remote_servers = $this->remote_gerrit_factory->getRemoteServersForUser($user);

        foreach($remote_servers as $remote_server) {
            try {
                $this->removeKeys($remote_server, $keys_to_add, $user);
                $this->removeKeys($remote_server, $keys_to_remove, $user);
            } catch (Git_Driver_Gerrit_RemoteSSHCommandFailure $e) {
                $errors[] = $e->getTraceAsString();
            }
        }
        //double foreach to workaround gerrit bug
        foreach($remote_servers as $remote_server) {
            try {
                $this->addKeys($remote_server, $keys_to_add, $user);
            } catch (Git_Driver_Gerrit_RemoteSSHCommandFailure $e) {
                $errors[] = $e->getTraceAsString();
            }
        }
        
        if ($errors) {
            $message = implode(PHP_EOL, $errors);
            throw new Git_UserSynchronisationException($message);
        }
    }

    /**
     * Makes sure there is one copy of each key on each remote server
     * 
     * @param Git_RemoteServer_GerritServerFactory $remote_gerrit_factory
     * @return void
     * @throws Git_UserSynchronisationException
     */
    public function pushSSHKeys(PFUser $user) {
        if (! $user->isLDAP()) {
            return;
        }
        
        $user_keys = array_unique($user->getAuthorizedKeysArray());

        if (! $user_keys) {
            return;
        }

        $errors = array();
        $remote_servers = $this->remote_gerrit_factory->getRemoteServersForUser($user);
        
        foreach($remote_servers as $remote_server) {
           try { 
                $this->removeKeys($remote_server, $user_keys, $user);
                $this->addKeys($remote_server, $user_keys, $user);
            } catch (Git_Driver_Gerrit_RemoteSSHCommandFailure $e) {
                $errors[] = $e->getTraceAsString();
            }
        }

        if ($errors) {
            $message = implode(PHP_EOL, $errors);
            throw new Git_UserSynchronisationException($message);
        }
    }

    /**
     *
     * @param Git_RemoteServer_GerritServer $remote_server
     * @param array $keys
     * @throws Git_Driver_Gerrit_RemoteSSHCommandFailure
     */
    private function addKeys(Git_RemoteServer_GerritServer $remote_server, Array $keys, PFUser $user) {
        foreach($keys as $key) {
            $this->gerrit_driver->addSSHKeyToAccount($remote_server, $user, $key);
        }
    }

    /**
     *
     * @param Git_RemoteServer_GerritServer $remote_server
     * @param array $keys
     * @throws Git_Driver_Gerrit_RemoteSSHCommandFailure
     */
    private function removeKeys(Git_RemoteServer_GerritServer $remote_server, Array $keys, PFUser $user) {
        foreach($keys as $key) {
            $this->gerrit_driver->removeSSHKeyFromAccount($remote_server, $user, $key);
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
