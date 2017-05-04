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

namespace Tuleap\Configuration\Mysql;

use Tuleap\Configuration\Vars;

class BackendSVN
{

    private $ip_address;
    private $dbauthuser_password;
    private $db_host;
    private $db_password;

    public function __construct(Vars $vars, $ip_address)
    {
        $this->db_host             = $vars->getDatabaseServer();
        $this->db_password         = $vars->getDatabaseRootPassword();
        $this->ip_address          = $ip_address;
        $this->dbauthuser_password = $vars->getDbauthuserPassword();
    }

    public function configure()
    {
        $this->grantDBAuthUserAccessToTablesNeedBySVN();
    }

    private function grantDBAuthUserAccessToTablesNeedBySVN()
    {
        $mysqli = new \mysqli($this->db_host, 'root', $this->db_password, 'mysql');
        if ($mysqli->connect_error) {
            throw new \Exception('Unable to connect to DB (' . $mysqli->connect_errno . ') ' . $mysqli->connect_error);
        }

        $mysqli->query('GRANT SELECT ON tuleap.user to dbauthuser@\''.$this->ip_address.'\' identified by \''.$this->dbauthuser_password.'\'');
        $mysqli->query('GRANT SELECT ON tuleap.user_group to dbauthuser@\''.$this->ip_address.'\'');
        $mysqli->query('GRANT SELECT ON tuleap.groups to dbauthuser@\''.$this->ip_address.'\'');
        $mysqli->query('GRANT SELECT ON tuleap.svn_token to dbauthuser@\''.$this->ip_address.'\'');
        $mysqli->query('GRANT SELECT ON tuleap.plugin_ldap_user to dbauthuser@\''.$this->ip_address.'\'');
        $mysqli->query('FLUSH PRIVILEGES');
        $mysqli->close();
    }
}
