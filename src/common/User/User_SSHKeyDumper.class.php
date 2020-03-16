<?php
/**
 * Copyright (c) Enalean, 2012. All Rights Reserved.
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

/**
 * This class is very sensitive because it stronly interact with secuirty
 * mecanisms and it's designed to be run by root
 *
 * Moreover it's uterly complex to test as:
 * - it relies on system stuff (chgrp, chown)
 * - it change current process UID/GID to write keys
 *
 * The process change was introduced to avoid one user to take over ssh account
 * of another one by creating a symlink on target account (see tests for more details).
 *
 * In other words: do not modify this part if you are not a trained warrior and
 * for reviewers: review carfully and test to destroy the code.
 */
class User_SSHKeyDumper
{
    /**
     * @var Backend
     */
    private $backend;

    public function __construct(Backend $backend)
    {
        $this->backend = $backend;
    }

    /**
     * Write SSH authorized_keys into a user homedir
     *
     *
     * @return bool
     */
    public function writeSSHKeys(PFUser $user)
    {
        try {
            if ($user->getUnixStatus() != 'A') {
                return true;
            }
            $ssh_dir  = $user->getUnixHomeDir() . '/.ssh';

            // Subtlety: between the 2 process owner change, there is no way to
            // write any logs because the process is owned by a mere user but
            // the log file is only writtable by codendiadm and root. So the
            // exceptions... welcome to the real world Neo.
            $this->changeProcessUidGidToUser($user);
            $this->createSSHDirForUser($user, $ssh_dir);
            $this->writeSSHFile($user, $ssh_dir);
            $this->restoreRootUidGid();

            $this->backend->changeOwnerGroupMode($ssh_dir, $user->getUserName(), $user->getUserName(), 0700);
            $this->backend->changeOwnerGroupMode("$ssh_dir/authorized_keys", $user->getUserName(), $user->getUserName(), 0600);

            $this->backend->log("Authorized_keys for " . $user->getUserName() . " written.", Backend::LOG_INFO);
            return true;
        } catch (Exception $exception) {
            $this->restoreRootUidGid();
            $this->backend->log($exception->getMessage(), Backend::LOG_ERROR);
            return false;
        }
    }

    protected function changeProcessUidGidToUser(PFUser $user)
    {
        $user_unix_info = posix_getpwnam($user->getUserName());
        if (empty($user_unix_info['uid']) || empty($user_unix_info['gid'])) {
            throw new RuntimeException("User " . $user->getUserName() . " has no uid/gid");
        }
        if (!(posix_setegid($user_unix_info['gid']) && posix_seteuid($user_unix_info['uid']))) {
            throw new RuntimeException("Cannot change current process uid/gid for " . $user->getUserName());
        }
    }

    protected function restoreRootUidGid()
    {
        posix_setegid(0);
        posix_seteuid(0);
    }

    private function createSSHDirForUser(PFUser $user, $ssh_dir)
    {
        if (is_link($ssh_dir)) {
            $link_path = readlink($ssh_dir);
            unlink($ssh_dir);
            throw new RuntimeException('SECURITY ISSUE! User "' . $user->getUserName() . '" made a symbolic link on it\'s .ssh dir (was a link to "' . $link_path . '"). Link was deleted but you should investigate.');
        }
        if (!is_dir($ssh_dir)) {
            if (mkdir($ssh_dir)) {
                $this->backend->chmod($ssh_dir, 0700);
            } else {
                throw new RuntimeException("Unable to create user home ssh directory for " . $user->getUserName());
            }
        }
    }

    private function writeSSHFile(PFUser $user, $ssh_dir)
    {
        $authorized_keys_new = "$ssh_dir/authorized_keys_new";
        touch($authorized_keys_new);
        $this->backend->chmod($authorized_keys_new, 0600);

        $ssh_keys = implode("\n", $user->getAuthorizedKeysArray());
        if (file_put_contents($authorized_keys_new, $ssh_keys) === false) {
            throw new RuntimeException("Unable to write authorized_keys_new file for " . $user->getUserName());
        }
        if (rename($authorized_keys_new, "$ssh_dir/authorized_keys") === false) {
            throw new RuntimeException("Unable to rename $authorized_keys_new file for " . $user->getUserName());
        }
    }
}
