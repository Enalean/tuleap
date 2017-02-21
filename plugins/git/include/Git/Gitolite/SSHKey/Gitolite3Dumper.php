<?php
/**
 * Copyright (c) Enalean, 2017. All Rights Reserved.
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

namespace Tuleap\Git\Gitolite\SSHKey;

use IHaveAnSSHKey;
use System_Command;
use System_Command_CommandException;

class Gitolite3Dumper implements Dumper
{
    const GITOLITE_SHELL = '/usr/share/gitolite3/gitolite-shell';
    const AUTH_OPTIONS   = 'no-port-forwarding,no-X11-forwarding,no-agent-forwarding,no-pty';

    /**
     * @var AuthorizedKeysFileCreator
     */
    private $authorized_keys_file_creator;
    /**
     * @var System_Command
     */
    private $system_command;

    public function __construct(AuthorizedKeysFileCreator $authorized_keys_file_creator, System_Command $system_command)
    {
        $this->authorized_keys_file_creator = $authorized_keys_file_creator;
        $this->system_command               = $system_command;
    }

    /**
     * @return bool
     */
    public function dumpSSHKeys(IHaveAnSSHKey $user)
    {
        try {
            $this->dumpKeys();
        } catch (DumpKeyException $ex) {
            return false;
        }

        return true;
    }

    public function removeAllExistingKeysForUserName($user_name)
    {
        $this->dumpKeys();
    }

    /**
     * @throws DumpKeyException
     */
    public function dumpKeys()
    {
        $this->sanityCheck();
        $temporary_authorized_keys_file = tempnam(\ForgeConfig::get('tmp_dir'), 'gitolite3-authorized-keys');
        if ($temporary_authorized_keys_file === false) {
            throw new DumpKeyException('Could not create a temporary authorized keys file');
        }

        try {
            $this->authorized_keys_file_creator->dump(
                $temporary_authorized_keys_file,
                self::GITOLITE_SHELL,
                self::AUTH_OPTIONS
            );
        } catch (DumpKeyException $ex) {
            @unlink($temporary_authorized_keys_file);
            throw $ex;
        }

        try {
            $this->system_command->exec(
                'sudo /usr/share/tuleap/plugins/git/bin/replace-authorized-keys.sh ' . escapeshellarg($temporary_authorized_keys_file)
            );
        } catch (System_Command_CommandException $ex) {
            @unlink($temporary_authorized_keys_file);
            throw new DumpKeyException('Could not replace the authorized keys file with the newly generated one');
        }
    }

    private function sanityCheck()
    {
        if (! is_readable(self::GITOLITE_SHELL) || ! is_executable(self::GITOLITE_SHELL)) {
            throw new DumpKeyException('Gitolite shell is not readable or executable');
        }
    }
}
