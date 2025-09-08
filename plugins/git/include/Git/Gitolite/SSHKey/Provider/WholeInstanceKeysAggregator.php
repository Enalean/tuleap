<?php
/**
 * Copyright (c) Enalean, 2017 - Present. All Rights Reserved.
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

    #[\Override]
    public function current(): \Tuleap\Git\Gitolite\SSHKey\Key
    {
        return $this->iterator->current();
    }

    #[\Override]
    public function next(): void
    {
        $this->iterator->next();
    }

    #[\Override]
    public function key(): string|int|bool|float|null
    {
        return $this->iterator->key();
    }

    #[\Override]
    public function valid(): bool
    {
        return $this->iterator->valid();
    }

    #[\Override]
    public function rewind(): void
    {
        $this->iterator->rewind();
    }
}
