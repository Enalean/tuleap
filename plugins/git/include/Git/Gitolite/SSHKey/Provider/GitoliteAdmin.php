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
    const GITOLITE_ADMIN_KEY_NAME = 'id_rsa_gl-adm';
    const GITOLITE_ADMIN_KEY_PATH = '/home/codendiadm/.ssh/id_rsa_gl-adm.pub';

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
        $key = file_get_contents(self::GITOLITE_ADMIN_KEY_PATH);
        if ($key === false) {
            throw new AccessException('Could not read Gitolite admin key');
        }

        return new Key(self::GITOLITE_ADMIN_KEY_NAME, $key);
    }
}
