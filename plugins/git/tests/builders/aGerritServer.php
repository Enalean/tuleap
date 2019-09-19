<?php
/**
 * Copyright (c) Enalean, 2012 - 2014. All Rights Reserved.
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

function aGerritServer()
{
    return new Test_GerritServer_Builder();
}

class Test_GerritServer_Builder
{
    private $id;

    public function withId($id)
    {
        $this->id = $id;
        return $this;
    }

    public function build()
    {
        $host = $ssh_port = $http_port = $login = $identity_file = $replication_key = $use_ssl = $gerrit_version = $http_password = $auth_type = $replication_password = 0;
        return new Git_RemoteServer_GerritServer(
            $this->id,
            $host,
            $ssh_port,
            $http_port,
            $login,
            $identity_file,
            $replication_key,
            $use_ssl,
            $gerrit_version,
            $http_password,
            $replication_password,
            $auth_type
        );
    }
}
