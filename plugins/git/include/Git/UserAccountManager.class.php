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
class Git_UserAccountManager {

    /** @var PFUser */
    private $user;

    /** @var Git_Driver_Gerrit */
    private $gerrit_driver;

    /** @var Git_Driver_Gerrit_UserAccountManager */
    private $gerrit_user_account_manager;

    public function __construct(PFUser $user, Git_Driver_Gerrit $driver) {
        $this->user = $user;
        $this->gerrit_driver = $driver;
    }

    /**
     *
     * @param array $original_keys
     * @param array $new_keys
     * @throws Git_UserSynchronisationException
     */
    public function synchroniseSSHKeys(array $original_keys, array $new_keys, Git_RemoteServer_GerritServerFactory $factory) {
        if ($this->getGerritUserAccountManager()) {
            try {
                $this->synchroniseSSHKeysWithGerrit($original_keys, $new_keys, $factory);
            } catch (Git_Driver_Gerrit_UserSynchronisationException $e) {
                throw new Git_UserSynchronisationException($e->getTraceAsString());
            }
        } 
    }

    private function synchroniseSSHKeysWithGerrit($original_keys, $new_keys) {
        $gerrit_user_account_manager = $this->getGerritUserAccountManager();

        $gerrit_user_account_manager->synchroniseSSHKeys(
            $original_keys,
            $new_keys,
            $this->gerrit_server_factory
        );
    }

    /**
     *
     * @return Git_Driver_Gerrit_UserAccountManager | false
     */
    public function getGerritUserAccountManager() {
        if (! $this->gerrit_user_account_manager) {
            try {
                $this->gerrit_user_account_manager = new Git_Driver_Gerrit_UserAccountManager($this->user, $this->gerrit_driver);
            } catch (Git_Driver_Gerrit_InvalidLDAPUserException $e) {
                //not a gerrit user
                return false;
            }
        }

        return $this->gerrit_user_account_manager;
    }

    /**
     *
     * @param Git_Driver_Gerrit_UserAccountManager $manager
     * @return \Git_UserAccountManager
     */
    public function setGerritUserAccountManager(Git_Driver_Gerrit_UserAccountManager $manager) {
        $this->gerrit_user_account_manager = $manager;
        return $this;
    }
}
?>
