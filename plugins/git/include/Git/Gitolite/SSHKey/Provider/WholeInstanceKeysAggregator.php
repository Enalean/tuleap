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

class WholeInstanceKeysAggregator implements IProvideKey
{
    /**
     * @var \AppendIterator
     */
    private $iterator;

    public function __construct(GitoliteAdmin $gitolite_admin_key, GerritServer $gerrit_server_keys, User $user_keys)
    {
        $this->iterator = new \AppendIterator();
        $this->iterator->append($gitolite_admin_key);
        $this->iterator->append($gerrit_server_keys);
        $this->iterator->append($user_keys);
    }

    /**
     * @return \Tuleap\Git\Gitolite\SSHKey\Key
     */
    public function current()
    {
        return $this->iterator->current();
    }

    public function next()
    {
        $this->iterator->next();
    }

    public function key()
    {
        return $this->iterator->key();
    }

    public function valid()
    {
        return $this->iterator->valid();
    }

    public function rewind()
    {
        $this->iterator->rewind();
    }
}
