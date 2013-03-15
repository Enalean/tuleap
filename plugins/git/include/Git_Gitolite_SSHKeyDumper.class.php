<?php
/**
 * Copyright (c) Enalean, 2012. All Rights Reserved.
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
 * along with Tuleap; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

require_once 'common/user/IHaveAnSSHKey.php';
require_once 'Git_Exec.class.php';

class Git_Gitolite_SSHKeyDumper {

    private $admin_path;
    private $git_exec;
    private $user_manager;
    private $keydir;

    public function __construct($admin_path, Git_Exec $git_exec, UserManager $user_manager) {
        $this->admin_path   = $admin_path;
        $this->git_exec     = $git_exec;
        $this->user_manager = $user_manager;
        $this->keydir       = 'keydir';
    }

   /**
     * Dump ssh keys into gitolite conf
     */
    public function dumpSSHKeys(IHaveAnSSHKey $user = null) {
        if (is_dir($this->admin_path)) {
            $this->createKeydir();
            if ($user) {
                $this->initUserKeys($user);
                $commit_msg = 'Update '.$user->getUserName().' SSH keys';
            } else {
                $this->dumpAllKeys();
                $commit_msg = 'SystemEvent update all user keys';
            }
            if (is_dir($this->admin_path.'/keydir')) {
                $this->git_exec->add('keydir');
            }
            $this->git_exec->commit($commit_msg);
            return true;
        }
        return false;
    }

    private function dumpAllKeys() {
        $dumped_users = array();
        foreach ($this->user_manager->getUsersWithSshKey() as $user) {
            $dumped_users[$user->getUserName()] = true;
            $this->initUserKeys($user);
        }
        $this->purgeNotDumpedUsers($dumped_users);
    }

    private function purgeNotDumpedUsers(array $dumped_users) {
        foreach (glob($this->keydir.'/*.pub') as $file) {
            $file_name = basename($file);
            if ($file_name != 'id_rsa_gl-adm.pub') {
                $user_name = substr($file_name, 0, strpos($file_name, '@'));
                if (!isset($dumped_users[$user_name])) {
                    $this->git_exec->rm($file);
                }
            }
        }
    }

    private function initUserKeys(IHaveAnSSHKey $user) {
        $this->dumpKeys($user);
    }

    private function createKeydir() {
        clearstatcache();
        if (!is_dir($this->keydir)) {
            if (!mkdir($this->keydir)) {
                throw new Exception('Unable to create "keydir" directory in '.getcwd());
            }
        }
    }

    private function dumpKeys(IHaveAnSSHKey $user) {
        $i = 0;
        foreach ($user->getAuthorizedKeysArray() as $key) {
            $filePath = $this->admin_path.'/'.$this->keydir.'/'.$user->getUserName().'@'.$i.'.pub';
            $this->writeKeyIfChanged($filePath, $key);
            $i++;
        }
        $this->removeUserExistingKeys($user, $i);
    }

    private function writeKeyIfChanged($filePath, $key) {
        $changed = true;
        if (is_file($filePath)) {
            $stored_key = file_get_contents($filePath);
            if ($stored_key == $key) {
                $changed = false;
            }
        }
        if ($changed) {
            file_put_contents($filePath, $key);
        }
    }

    /**
     * Remove all pub SSH keys previously associated to a user
     *
     * @param IHaveAnSSHKey $user
     */
    private function removeUserExistingKeys(IHaveAnSSHKey $user, $last_key_id) {
        if (is_dir($this->keydir)) {
            $userbase = $user->getUserName().'@';
            foreach (glob("$this->keydir/$userbase*.pub") as $file) {
                $matches = array();
                if (preg_match('%^'.$this->keydir.'/'.$userbase.'([0-9]+).pub$%', $file, $matches)) {
                    if ($matches[1] >= $last_key_id) {
                        $this->git_exec->rm($file);
                    }
                }
            }
        }
    }
}

?>
