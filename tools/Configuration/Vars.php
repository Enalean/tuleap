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
 *
 */

namespace Tuleap\Configuration;

class Vars
{
    private $application_user;
    private $application_base_dir;
    private $server_name;
    private $database_server;
    private $database_root_password;
    private $dbauthuser_password;

    public function getApplicationUser()
    {
        return $this->application_user;
    }

    public function getApplicationBaseDir()
    {
        return $this->application_base_dir;
    }

    public function getServerName()
    {
        return $this->server_name;
    }

    public function getDatabaseServer()
    {
        return $this->database_server;
    }

    public function getDatabaseRootPassword()
    {
        return $this->database_root_password;
    }

    public function getDbauthuserPassword()
    {
        return $this->dbauthuser_password;
    }

    public function setApplicationUser($name)
    {
        $this->application_user = $name;
    }

    public function setApplicationBaseDir($dir)
    {
        $this->application_base_dir = $dir;
    }

    public function setServerName($name)
    {
        $this->server_name = $name;
    }

    public function setDatabaseServer($name)
    {
        $this->database_server = $name;
    }

    public function setDatabaseRootPassword($password)
    {
        $this->database_root_password = $password;
    }

    public function setDbauthuserPassword($password)
    {
        $this->dbauthuser_password = $password;
    }
}
