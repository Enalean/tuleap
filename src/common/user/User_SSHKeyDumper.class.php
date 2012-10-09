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

require_once 'common/backend/Backend.class.php';
require_once 'User.class.php';

class User_SSHKeyDumper {
    /**
     * @var Backend
     */
    private $backend;

    public function __construct(Backend $backend) {
        $this->backend = $backend;
    }

    /**
     * Write SSH authorized_keys into a user homedir
     *
     * /!\ Be careful, this method change current process UID/GID to write keys
     *
     * @param User $user
     *
     * @return Boolean
     */
    public function writeSSHKeys(User $user) {
        try {
            $ssh_dir  = $user->getUnixHomeDir().'/.ssh';

            $this->changeProcessUidGidToUser($user);
            $this->createSSHDirForUser($user, $ssh_dir);
            $this->writeSSHFile($user, $ssh_dir);
            $this->restoreRootUidGid();

            $this->backend->changeOwnerGroupMode($ssh_dir, $user->getUserName(), $user->getUserName(), 0700);
            $this->backend->changeOwnerGroupMode("$ssh_dir/authorized_keys", $user->getUserName(), $user->getUserName(), 0600);

            $this->backend->log("Authorized_keys for ".$user->getUserName()." written.", Backend::LOG_INFO);
            return true;
        } catch (Exception $exception) {
            $this->restoreRootUidGid();
            $this->backend->log($exception->getMessage(), Backend::LOG_ERROR);
            return false;
        }
    }

    protected function changeProcessUidGidToUser(User $user) {
        $user_unix_info = posix_getpwnam($user->getUserName());
        if (empty($user_unix_info['uid']) || empty($user_unix_info['gid'])) {
            throw new RuntimeException("User ".$user->getUserName()." has no uid/gid");
        }
        if (!(posix_setegid($user_unix_info['gid']) && posix_seteuid($user_unix_info['uid']))) {
            throw new RuntimeException("Cannot change current process uid/gid for ".$user->getUserName());
        }
    }

    protected function restoreRootUidGid() {
        posix_setegid(0);
        posix_seteuid(0);
    }

    private function createSSHDirForUser(User $user, $ssh_dir) {
        if (is_link($ssh_dir)) {
            unlink($ssh_dir);
            throw new RuntimeException('SECURITY ISSUE! User "'.$user->getUserName().'" made a symbolic link on it\'s .ssh dir. Link was deleted but you should investigate.');
        }
        if (!is_dir($ssh_dir)) {
            if (mkdir($ssh_dir)) {
                $this->backend->chmod($ssh_dir, 0700);
            } else {
                throw new RuntimeException("Unable to create user home ssh directory for ".$user->getUserName());
            }
        }
    }

    private function writeSSHFile(User $user, $ssh_dir) {
        $authorized_keys_new = "$ssh_dir/authorized_keys_new";
        touch($authorized_keys_new);
        $this->backend->chmod($authorized_keys_new, 0600);

        $ssh_keys = implode("\n", $user->getAuthorizedKeysArray());
        if (file_put_contents($authorized_keys_new, $ssh_keys) === false) {
            throw new RuntimeException("Unable to write authorized_keys_new file for ".$user->getUserName());
        }
        if (rename($authorized_keys_new, "$ssh_dir/authorized_keys") === false) {
            throw new RuntimeException("Unable to rename $authorized_keys_new file for ".$user->getUserName());
        }
    }
}

?>
