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

use Git_RemoteServer_Dao;
use Git_RemoteServer_Gerrit_ReplicationSSHKey;
use Tuleap\Git\Gitolite\SSHKey\Key;

class GerritServer implements IProvideKey
{
    /**
     * @var \ArrayIterator
     */
    private $gerrit_server_access_result;

    /**
     * @throws AccessException
     */
    public function __construct(Git_RemoteServer_Dao $gerrit_server_dao)
    {
        try {
            $this->gerrit_server_access_result = (new \ArrayObject($gerrit_server_dao->searchAllServersWithSSHKey()))->getIterator();
        } catch (\PDOException $ex) {
            throw new AccessException('Could not access Gerrit server keys');
        }
    }

    #[\Override]
    public function current(): Key
    {
        $row             = $this->gerrit_server_access_result->current();
        $replication_key = new Git_RemoteServer_Gerrit_ReplicationSSHKey();
        $replication_key->setGerritHostId($row['id'])->setValue($row['ssh_key']);
        return new Key($replication_key->getUserName(), $replication_key->getValue());
    }

    #[\Override]
    public function next(): void
    {
        $this->gerrit_server_access_result->next();
    }

    #[\Override]
    public function key(): mixed
    {
        return $this->gerrit_server_access_result->key();
    }

    #[\Override]
    public function valid(): bool
    {
        return $this->gerrit_server_access_result->valid();
    }

    #[\Override]
    public function rewind(): void
    {
        $this->gerrit_server_access_result->rewind();
    }
}
