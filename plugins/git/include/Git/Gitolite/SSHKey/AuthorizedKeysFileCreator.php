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

use Tuleap\Git\Gitolite\SSHKey\Provider\IProvideKey;

class AuthorizedKeysFileCreator
{
    /**
     * @var IProvideKey
     */
    private $keys;

    public function __construct(IProvideKey $keys)
    {
        $this->keys = $keys;
    }

    /**
     * @throws DumpKeyException
     */
    public function dump($file_path, $shell, $authentication_options)
    {
        $file = @fopen($file_path, 'wb');
        if ($file === false) {
            throw new DumpKeyException('Could not open the authorized keys file');
        }

        foreach ($this->keys as $key) {
            $this->dumpKey($file, $key, $shell, $authentication_options);
        }

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
        $result = fwrite(
            $file,
            'command="' . $shell . ' ' . $key->getUsername() . '",' . $authentication_options . ' ' .
            $key->getKey() . PHP_EOL
        );
        if ($result === null) {
            throw new DumpKeyException('Could not write to the authorized keys file');
        }
    }
}
