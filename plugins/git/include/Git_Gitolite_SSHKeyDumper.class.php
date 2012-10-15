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

require_once 'Git_Exec.class.php';

class Git_Gitolite_SSHKeyDumper {
    
    protected $admin_path;
    protected $git_exec;
    
    public function __construct($admin_path, Git_Exec $git_exec) {
        $this->admin_path = $admin_path;
        $this->git_exec   = $git_exec;
    }

    
   /**
     * Dump ssh keys into gitolite conf
     */
    public function dumpSSHKeys(User $user = null) {
        if (is_dir($this->admin_path)) {
            $keydir = 'keydir';
            $this->createKeydir($keydir);
            if ($user) {
                $this->initUserKeys($user, $keydir);
                $commit_msg = 'Update '.$user->getUserName().' (Id: '.$user->getId().') SSH keys';
            } else {
                $userdao = new UserDao();
                foreach ($userdao->searchSSHKeys() as $row) {
                    $user = new User($row);
                    $this->initUserKeys($user, $keydir);
                }
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

    private function initUserKeys(User $user, $keydir) {
        $this->dumpKeys($user, $keydir);
    }

    private function createKeydir($keydir) {
        clearstatcache();
        if (!is_dir($keydir)) {
            if (!mkdir($keydir)) {
                throw new Exception('Unable to create "keydir" directory in '.getcwd());
            }
        }
    }

    private function dumpKeys(User $user, $keydir) {
        $i = 0;
        foreach ($user->getAuthorizedKeysArray() as $key) {
            $filePath = $keydir.'/'.$user->getUserName().'@'.$i.'.pub';
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
     * @param User $user
     */
    private function removeUserExistingKeys($user, $last_key_id) {
        $keydir = 'keydir';
        if (is_dir($keydir)) {
            $userbase = $user->getUserName().'@';
            foreach (glob("$keydir/$userbase*.pub") as $file) {
                $matches = array();
                if (preg_match('%^'.$keydir.'/'.$userbase.'([0-9]+).pub$%', $file, $matches)) {
                    if ($matches[1] >= $last_key_id) {
                        $this->git_exec->rm($file);
                    }
                }
            }
        }
    }
}

?>
