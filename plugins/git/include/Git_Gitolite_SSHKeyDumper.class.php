<?php
/**
 * Copyright (c) Enalean, 2012 - 2018. All Rights Reserved.
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

use Tuleap\Git\Gitolite\SSHKey\Dumper;
use Tuleap\Git\Gitolite\SSHKey\InvalidKeysCollector;

class Git_Gitolite_SSHKeyDumper implements Dumper
{
    public const KEYDIR       = 'keydir';
    public const FIRST_KEY_ID = 0;

    private $admin_path;
    private $git_exec;

    public function __construct($admin_path, Git_Exec $git_exec)
    {
        $this->admin_path   = $admin_path;
        $this->git_exec     = $git_exec;
    }

    /**
     * Absolute path to gitolite keydir
     *
     * @return String
     */
    public function getKeyDirPath()
    {
        return $this->admin_path . '/' . self::KEYDIR;
    }

    /**
     * Return Git_Exec object
     *
     * @return Git_Exec
     */
    public function getGitExec()
    {
        return $this->git_exec;
    }

    /**
     * Dump ssh keys into gitolite conf
     *
     * @return bool
     */
    public function dumpSSHKeys(IHaveAnSSHKey $user, InvalidKeysCollector $invalid_keys_collector)
    {
        $this->dumpSSHKeysWithoutCommit($user);
        if ($this->commitKeyDir('Update ' . $user->getUserName() . ' SSH keys')) {
            return $this->git_exec->push();
        }
        return false;
    }

    /**
     * Add pending modification to index and commit with message
     *
     * @param String $message
     *
     * @return bool
     */
    public function commitKeyDir($message)
    {
        clearstatcache();
        if (is_dir($this->getKeyDirPath())) {
            $this->git_exec->add($this->getKeyDirPath());
        }
        return $this->git_exec->commit($message);
    }

    /**
     * Dump user SSH key
     *
     *
     * @return bool
     */
    public function dumpSSHKeysWithoutCommit(IHaveAnSSHKey $user)
    {
        if (is_dir($this->admin_path)) {
            $this->createKeydir();
            $this->initUserKeys($user);
            return true;
        }
        return false;
    }

    /**
     * Remove all pub SSH keys previously associated to a user
     *
     * @param $user_name
     */
    public function removeAllExistingKeysForUserName($user_name)
    {
        $this->removeUserExistingKeysFromAGivenKeyId($user_name, self::FIRST_KEY_ID);
    }

    private function initUserKeys(IHaveAnSSHKey $user)
    {
        $this->dumpKeys($user);
    }

    private function createKeydir()
    {
        clearstatcache();
        if (!is_dir($this->getKeyDirPath())) {
            if (!mkdir($this->getKeyDirPath())) {
                throw new Exception('Unable to create "' . $this->getKeyDirPath() . '" directory in ');
            }
        }
    }

    private function dumpKeys(IHaveAnSSHKey $user)
    {
        $ssh_key_id = 0;
        $user_name  = $user->getUserName();

        foreach ($user->getAuthorizedKeysArray() as $key) {
            $filePath = $this->getKeyDirPath() . '/' . $user_name . '@' . $ssh_key_id . '.pub';
            $this->writeKeyIfChanged($filePath, $key);
            $ssh_key_id++;
        }
        $this->removeUserExistingKeysFromAGivenKeyId($user_name, $ssh_key_id);
    }

    private function writeKeyIfChanged($filePath, $key)
    {
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
     * @param int           $last_key_id
     */
    private function removeUserExistingKeysFromAGivenKeyId($user_name, $last_key_id)
    {
        if (is_dir($this->getKeyDirPath())) {
            $userbase = $user_name . '@';
            foreach (glob($this->getKeyDirPath() . "/$userbase*.pub") as $file) {
                if ($this->getKeyNumber($userbase, $file) >= $last_key_id) {
                    $this->git_exec->rm($file);
                }
            }
        }
    }

    private function getKeyNumber($userbase, $file)
    {
        $matches = array();
        if (preg_match('%^' . $this->getKeyDirPath() . '/' . $userbase . '([0-9]+).pub$%', $file, $matches)) {
            return intval($matches[1]);
        }
        return -1;
    }
}
