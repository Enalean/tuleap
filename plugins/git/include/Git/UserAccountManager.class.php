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
class Git_UserAccountManager
{

    /** @var Git_Driver_Gerrit_GerritDriverFactory */
    private $gerrit_driver_factory;

    /** @var Git_Driver_Gerrit_UserAccountManager */
    private $gerrit_user_account_manager;

    /** @var Git_RemoteServer_GerritServerFactory */
    private $gerrit_server_factory;

    public function __construct(
        Git_Driver_Gerrit_GerritDriverFactory $driver_factory,
        Git_RemoteServer_GerritServerFactory $factory
    ) {
        $this->gerrit_driver_factory = $driver_factory;
        $this->gerrit_server_factory = $factory;
    }

    /**
     *
     * @param array $original_keys
     * @param array $new_keys
     * @throws Git_UserSynchronisationException
     */
    public function synchroniseSSHKeys(array $original_keys, array $new_keys, PFUser $user)
    {
        $this->synchroniseSSHKeysWithGerrit($original_keys, $new_keys, $user);
    }

    private function synchroniseSSHKeysWithGerrit($original_keys, $new_keys, $user)
    {
        $this->getGerritUserAccountManager()
            ->synchroniseSSHKeys(
                $original_keys,
                $new_keys,
                $user
            );
    }

    /**
     *
     * @param Git_RemoteServer_GerritServerFactory $factory
     * @return void
     * @throws Git_UserSynchronisationException
     */
    public function pushSSHKeys(PFUser $user)
    {
        $this->pushSSHKeysToGerrit($user);
    }

    private function pushSSHKeysToGerrit($user)
    {
        $this->getGerritUserAccountManager()->pushSSHKeys($user);
    }

    /**
     *
     * @return Git_Driver_Gerrit_UserAccountManager
     */
    public function getGerritUserAccountManager()
    {
        if (! $this->gerrit_user_account_manager) {
            $this->gerrit_user_account_manager = new Git_Driver_Gerrit_UserAccountManager(
                $this->gerrit_driver_factory,
                $this->gerrit_server_factory
            );
        }

        return $this->gerrit_user_account_manager;
    }

    /**
     *
     * @return Git_UserAccountManager
     */
    public function setGerritUserAccountManager(Git_Driver_Gerrit_UserAccountManager $manager)
    {
        $this->gerrit_user_account_manager = $manager;
        return $this;
    }
}
