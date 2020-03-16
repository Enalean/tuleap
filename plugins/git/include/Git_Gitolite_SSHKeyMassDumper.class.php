<?php
/**
 * Copyright Enalean (c) 2011 - 2017. All rights reserved.
 *
 * Tuleap and Enalean names and logos are registrated trademarks owned by
 * Enalean SAS. All other trademarks or names are properties of their respective
 * owners.
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

use Tuleap\Git\Gitolite\SSHKey\InvalidKeysCollector;
use Tuleap\Git\Gitolite\SSHKey\MassDumper;

class Git_Gitolite_SSHKeyMassDumper implements MassDumper
{
    private $dumper;
    private $user_manager;

    public function __construct(Git_Gitolite_SSHKeyDumper $dumper, UserManager $user_manager)
    {
        $this->dumper       = $dumper;
        $this->user_manager = $user_manager;
    }

    public function dumpSSHKeys(InvalidKeysCollector $invalid_keys_collector)
    {
        $this->dumpAllKeys();
        if ($this->dumper->commitKeyDir('SystemEvent update all user keys')) {
            return $this->dumper->getGitExec()->push();
        }
        return false;
    }

    private function dumpAllKeys()
    {
        $dumped_users = array();
        foreach ($this->user_manager->getUsersWithSshKey() as $user) {
            $dumped_users[$user->getUserName()] = true;
            $this->dumper->dumpSSHKeysWithoutCommit($user);
        }
        $this->purgeNotDumpedUsers($dumped_users);
    }

    private function purgeNotDumpedUsers(array $dumped_users)
    {
        foreach (glob($this->dumper->getKeyDirPath() . '/*.pub') as $file) {
            $file_name = basename($file);
            if (!$this->isReservedName($file_name)) {
                $user_name = substr($file_name, 0, strpos($file_name, '@'));
                if (!isset($dumped_users[$user_name])) {
                    $this->dumper->getGitExec()->rm($file);
                }
            }
        }
    }

    private function isReservedName($file_name)
    {
        if ($this->isAdminKey($file_name) || $this->isGerritKey($file_name)) {
            return true;
        }
        return false;
    }

    private function isAdminKey($file_name)
    {
        return $file_name == 'id_rsa_gl-adm.pub';
    }

    private function isGerritKey($file_name)
    {
        return strpos($file_name, Rule_UserName::RESERVED_PREFIX . Git_RemoteServer_Gerrit_ReplicationSSHKey::KEYNAME_PREFIX) === 0;
    }
}
