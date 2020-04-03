<?php
/**
 * Copyright (c) Enalean, 2013 - 2014. All Rights Reserved.
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
class Git_Driver_Gerrit_UserAccountManager
{

    /**
     * @var Git_Driver_Gerrit_GerritDriverFactory
     */
    private $gerrit_driver_factory;

    /**
     * @var Git_RemoteServer_GerritServerFactory
     */
    private $remote_gerrit_factory;

    public function __construct(
        Git_Driver_Gerrit_GerritDriverFactory $gerrit_driver_factory,
        Git_RemoteServer_GerritServerFactory $remote_gerrit_factory
    ) {
        $this->gerrit_driver_factory = $gerrit_driver_factory;
        $this->remote_gerrit_factory = $remote_gerrit_factory;
    }

    /**
     *
     *
     * @return Git_Driver_Gerrit_User|null
     */
    public function getGerritUser(PFUser $user)
    {
        $ldap_user = null;
        $params    = array('ldap_user' => &$ldap_user, 'user' => $user);
        EventManager::instance()->processEvent(Event::GET_LDAP_LOGIN_NAME_FOR_USER, $params);
        if ($ldap_user) {
            return new Git_Driver_Gerrit_User($ldap_user);
        } else {
            return null;
        }
    }

    /**
     *
     * @return bool
     */
    private function isGerrit(PFUser $user)
    {
        return ($this->getGerritUser($user) !== null);
    }

    /**
     *
     * @param array $original_keys
     * @param array $new_keys
     * @param Git_RemoteServer_GerritServerFactory $remote_gerrit_factory
     * @return void
     * @throws Git_UserSynchronisationException
     */
    public function synchroniseSSHKeys(array $original_keys, array $new_keys, PFUser $user)
    {
        if (! $this->isGerrit($user)) {
            return;
        }

        $gerrit_user = $this->getGerritUser($user);

        $keys_to_add    = $this->getKeysToAdd($original_keys, $new_keys);
        $keys_to_remove = $this->getKeysToRemove($original_keys, $new_keys);

        if (! $this->areThereKeysToUpdate($keys_to_add, $keys_to_remove)) {
            return;
        }

        $errors = array();
        $remote_servers = $this->remote_gerrit_factory->getRemoteServersForUser($user);

        foreach ($remote_servers as $remote_server) {
            $errors += $this->removeKeys($remote_server, $keys_to_add, $gerrit_user);
            $errors += $this->removeKeys($remote_server, $keys_to_remove, $gerrit_user);
        }
        //double foreach to workaround gerrit bug
        foreach ($remote_servers as $remote_server) {
            $errors += $this->addKeys($remote_server, $keys_to_add, $gerrit_user);
        }

        if (count($errors) > 0) {
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
    public function pushSSHKeys(PFUser $user)
    {
        if (! $this->isGerrit($user)) {
            return;
        }

        $gerrit_user = $this->getGerritUser($user);
        $user_keys = array_unique($user->getAuthorizedKeysArray());

        if (! $user_keys) {
            return;
        }

        $errors = array();
        $remote_servers = $this->remote_gerrit_factory->getRemoteServersForUser($user);

        foreach ($remote_servers as $remote_server) {
            $errors += $this->removeKeys($remote_server, $user_keys, $gerrit_user);
            $errors += $this->addKeys($remote_server, $user_keys, $gerrit_user);
        }

        if (count($errors) > 0) {
            $message = implode(PHP_EOL, $errors);
            throw new Git_UserSynchronisationException($message);
        }
    }

    /**
     *
     * @param array $keys
     *
     * @return string[] List of errors
     */
    private function addKeys(Git_RemoteServer_GerritServer $remote_server, array $keys, Git_Driver_Gerrit_User $gerrit_user)
    {
        $errors = array();
        foreach ($keys as $key) {
            try {
                $this->gerrit_driver_factory->getDriver($remote_server)->addSSHKeyToAccount($remote_server, $gerrit_user, $key);
            } catch (Git_Driver_Gerrit_Exception $exception) {
                $errors[] = $exception->getMessage();
            }
        }
        return $errors;
    }

    /**
     *
     * @param array $keys
     *
     * @return string[] List of errors
     */
    private function removeKeys(Git_RemoteServer_GerritServer $remote_server, array $keys, Git_Driver_Gerrit_User $gerrit_user)
    {
        $errors = array();
        foreach ($keys as $key) {
            try {
                $this->gerrit_driver_factory->getDriver($remote_server)->removeSSHKeyFromAccount($remote_server, $gerrit_user, $key);
            } catch (Git_Driver_Gerrit_Exception $exception) {
                $errors[] = $exception->getMessage();
            }
        }
        return $errors;
    }

    /**
     *
     * @param array $original_keys
     * @param array $new_keys
     * @return array
     */
    private function getKeysToAdd(array $original_keys, array $new_keys)
    {
        return array_unique(array_diff($new_keys, $original_keys));
    }

    /**
     *
     * @param array $original_keys
     * @param array $new_keys
     * @return array
     */
    private function getKeysToRemove(array $original_keys, array $new_keys)
    {
        return array_unique(array_diff($original_keys, $new_keys));
    }

    /**
     *
     * @param array $keys_to_add
     * @param array $keys_to_remove
     * @return array
     */
    private function areThereKeysToUpdate(array $keys_to_add, array $keys_to_remove)
    {
        return $keys_to_add || $keys_to_remove;
    }
}
