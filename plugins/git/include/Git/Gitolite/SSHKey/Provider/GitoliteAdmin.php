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

namespace Tuleap\Git\Gitolite\SSHKey\Provider;

use ArrayIterator;
use Tuleap\Git\Gitolite\SSHKey\Key;

class GitoliteAdmin extends ArrayIterator implements IProvideKey
{
    public const GITOLITE_ADMIN_KEY_NAME = 'id_rsa_gl-adm';
    public const GITOLITE_ADMIN_KEY_FILE = 'id_rsa_gl-adm.pub';
    public const EL6_HOME                = '/home/codendiadm';
    public const EL7_HOME                = '/var/lib/tuleap';

    public function __construct()
    {
        $keys = array($this->getGitoliteAdminKey());
        parent::__construct($keys);
    }

    /**
     * @return Key
     * @throws AccessException
     */
    private function getGitoliteAdminKey()
    {
        $key = file_get_contents($this->getFile([self::EL7_HOME, self::EL6_HOME]));
        if ($key === false) {
            throw new AccessException('Could not read Gitolite admin key');
        }

        return new Key(self::GITOLITE_ADMIN_KEY_NAME, $key);
    }

    /**
     * @param array $paths
     * @return string
     * @throws AccessException
     */
    private function getFile(array $paths)
    {
        foreach ($paths as $path) {
            $file_path = $path . '/.ssh/' . self::GITOLITE_ADMIN_KEY_FILE;
            if (is_file($file_path)) {
                return $file_path;
            }
        }
        throw new AccessException("No valid " . self::GITOLITE_ADMIN_KEY_FILE . " found");
    }
}
