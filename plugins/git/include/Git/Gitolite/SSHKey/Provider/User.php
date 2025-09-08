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

use ArrayIterator;
use Tuleap\Git\Gitolite\SSHKey\Key;
use UserManager;

class User implements IProvideKey
{
    private ArrayIterator $user_with_ssh_keys;
    /**
     * @var ArrayIterator
     */
    private $user_ssh_key_iterator;
    /**
     * @var int
     */
    private $current_position;
    /**
     * @var string
     */
    private $current_username;

    public function __construct(UserManager $user_manager)
    {
        $this->user_with_ssh_keys = new ArrayIterator($user_manager->getUsersWithSshKey());
        $this->rewind();
    }

    #[\Override]
    public function current(): Key
    {
        return new Key($this->current_username, $this->user_ssh_key_iterator->current());
    }

    #[\Override]
    public function next(): void
    {
        $this->current_position++;
        $this->user_ssh_key_iterator->next();
        if ($this->user_ssh_key_iterator->valid()) {
            return;
        }
        if (! $this->user_with_ssh_keys->valid()) {
            return;
        }
        $user                        = $this->user_with_ssh_keys->current();
        $this->current_username      = $user->getUsername();
        $this->user_ssh_key_iterator = new ArrayIterator($user->getAuthorizedKeysArray());
        $this->user_with_ssh_keys->next();
    }

    #[\Override]
    public function key(): int
    {
        return $this->current_position;
    }

    #[\Override]
    public function valid(): bool
    {
        return $this->user_ssh_key_iterator->valid();
    }

    #[\Override]
    public function rewind(): void
    {
        $this->user_with_ssh_keys->rewind();
        $this->current_position      = 0;
        $this->user_ssh_key_iterator = new ArrayIterator();
        $this->next();
    }
}
