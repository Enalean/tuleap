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

use System_Command;
use System_Command_CommandException;
use Tuleap\Git\Gitolite\SSHKey\Provider\IProvideKey;

class AuthorizedKeysFileCreator
{
    /**
     * @var IProvideKey
     */
    private $keys;
    /**
     * @var System_Command
     */
    private $system_command;

    public function __construct(IProvideKey $keys, System_Command $system_command)
    {
        $this->keys           = $keys;
        $this->system_command = $system_command;
    }

    /**
     * @throws DumpKeyException
     */
    public function dump($file_path, $shell, $authentication_options, InvalidKeysCollector $invalid_keys_collector)
    {
        try {
            $this->dumpAllKeysAtOnce($file_path, $shell, $authentication_options);
        } catch (MalformedAuthorizedKeysFileException $ex) {
            $this->dumpKeysFilteringInvalidOnes($file_path, $shell, $authentication_options, $invalid_keys_collector);
        }
    }

    /**
     * @throws MalformedAuthorizedKeysFileException
     * @throws DumpKeyException
     */
    private function dumpAllKeysAtOnce($file_path, $shell, $authentication_options)
    {
        $file = $this->openAuthorizedKeysFile($file_path);

        foreach ($this->keys as $key) {
            $this->dumpKey($file, $key, $shell, $authentication_options);
        }

        $this->closeAuthorizedKeysFile($file);

        if (! $this->isAuthorizedKeysValid($file_path)) {
            throw new MalformedAuthorizedKeysFileException('The generated authorized keys file contains invalid entries');
        }
    }

    /**
     * @throws DumpKeyException
     */
    private function dumpKeysFilteringInvalidOnes(
        $file_path,
        $shell,
        $authentication_options,
        InvalidKeysCollector $invalid_keys_collector
    ) {
        $file = $this->openAuthorizedKeysFile($file_path);

        $temporary_authorized_key_line_file = tempnam(\ForgeConfig::get('tmp_dir'), 'gitolite3-authorized-key-line');
        if ($temporary_authorized_key_line_file === false) {
            @unlink($temporary_authorized_key_line_file);
            throw new DumpKeyException('Could not create a temporary file to verify an authorized key line');
        }

        foreach ($this->keys as $key) {
            $this->dumpKeyIfValid(
                $file,
                $temporary_authorized_key_line_file,
                $key,
                $shell,
                $authentication_options,
                $invalid_keys_collector
            );
        }

        @unlink($temporary_authorized_key_line_file);
        $this->closeAuthorizedKeysFile($file);
    }

    /**
     * @return resource
     * @throws DumpKeyException
     */
    private function openAuthorizedKeysFile($file_path)
    {
        $file = @fopen($file_path, 'wb');
        if ($file === false) {
            throw new DumpKeyException('Could not open the authorized keys file');
        }
        return $file;
    }

    /**
     * @throws DumpKeyException
     */
    private function closeAuthorizedKeysFile($file)
    {
        if (fflush($file) === false) {
            throw new DumpKeyException('Could not flush content to the authorized keys file');
        }
        if (fclose($file) === false) {
            throw new DumpKeyException('Could not close the authorized keys file');
        }
    }

    /**
     * @throws DumpKeyException
     */
    private function dumpKey($file, Key $key, $shell, $authentication_options)
    {
        $result = fwrite($file, $this->getAuthorizedKeyLine($key, $shell, $authentication_options));
        if ($result === null) {
            throw new DumpKeyException('Could not write to the authorized keys file');
        }
    }

    /**
     * @throws DumpKeyException
     */
    private function dumpKeyIfValid(
        $file,
        $temporary_authorized_key_line_file,
        Key $key,
        $shell,
        $authentication_options,
        InvalidKeysCollector $invalid_keys_collector
    ) {
        $authorized_key_line = $this->getAuthorizedKeyLine($key, $shell, $authentication_options);

        $is_temporary_authorized_key_line_file_written = file_put_contents(
            $temporary_authorized_key_line_file,
            $authorized_key_line
        );

        if ($is_temporary_authorized_key_line_file_written === false) {
            @unlink($temporary_authorized_key_line_file);
            throw new DumpKeyException('Could not write to the temporary file to verify an authorized key line');
        }

        if (! $this->isAuthorizedKeysValid($temporary_authorized_key_line_file)) {
            $invalid_keys_collector->add($key);
            return;
        }

        $result = fwrite($file, $this->getAuthorizedKeyLine($key, $shell, $authentication_options));
        if ($result === null) {
            throw new DumpKeyException('Could not write to the authorized keys file');
        }
    }

    /**
     * @return string
     */
    private function getAuthorizedKeyLine(Key $key, $shell, $authentication_options)
    {
        return 'command="' . $shell . ' ' . $key->getUsername() . '",' . $authentication_options . ' ' .
            $key->getKey() . PHP_EOL;
    }

    /**
     * @return bool
     */
    private function isAuthorizedKeysValid($file_path)
    {
        try {
            $this->system_command->exec(
                '/usr/share/tuleap/src/utils/ssh-keys-validity-checker.sh ' . escapeshellarg($file_path)
            );
        } catch (System_Command_CommandException $ex) {
            return false;
        }

        return true;
    }
}
