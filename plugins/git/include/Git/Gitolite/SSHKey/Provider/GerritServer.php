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

use Git_RemoteServer_Dao;
use Git_RemoteServer_Gerrit_ReplicationSSHKey;
use Tuleap\Git\Gitolite\SSHKey\Key;

class GerritServer implements IProvideKey
{
    /**
     * @var \DataAccessResult
     */
    private $gerrit_server_access_result;

    /**
     * @throws AccessException
     */
    public function __construct(Git_RemoteServer_Dao $gerrit_server_dao)
    {
        $this->gerrit_server_access_result = $gerrit_server_dao->searchAllServersWithSSHKey();
        if ($this->gerrit_server_access_result === false) {
            throw new AccessException('Could not access Gerrit server keys');
        }
    }

    /**
     * @return Key
     */
    public function current()
    {
        $row             = $this->gerrit_server_access_result->current();
        $replication_key = new Git_RemoteServer_Gerrit_ReplicationSSHKey();
        $replication_key->setGerritHostId($row['id'])->setValue($row['ssh_key']);
        return new Key($replication_key->getUserName(), $replication_key->getValue());
    }

    public function next()
    {
        $this->gerrit_server_access_result->next();
    }

    public function key()
    {
        return $this->gerrit_server_access_result->key();
    }

    public function valid()
    {
        return $this->gerrit_server_access_result->valid();
    }

    public function rewind()
    {
        $this->gerrit_server_access_result->rewind();
    }
}
