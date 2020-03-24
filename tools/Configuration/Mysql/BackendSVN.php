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
    private $app_dir;

    public function __construct(Vars $vars, $ip_address)
    {
        $this->db_host             = $vars->getDatabaseServer();
        $this->db_password         = $vars->getDatabaseRootPassword();
        $this->ip_address          = $ip_address;
        $this->dbauthuser_password = $vars->getDbauthuserPassword();
        $this->app_dir             = $vars->getApplicationBaseDir();
    }

    public function configure()
    {
        $output = [];
        $return_value = 0;
        $command = sprintf(
            '%s/src/tuleap-cfg/tuleap-cfg.php setup:mysql-init --host=%s --admin-user=%s --admin-password=%s --db-name=%s --nss-user=%s@%s --nss-password=%s 2>&1',
            escapeshellarg($this->app_dir),
            escapeshellarg($this->db_host),
            escapeshellarg('root'),
            escapeshellarg($this->db_password),
            escapeshellarg('tuleap'),
            escapeshellarg('dbauthuser'),
            escapeshellarg($this->ip_address),
            escapeshellarg($this->dbauthuser_password),
        );
        exec(
            $command,
            $output,
            $return_value,
        );
        if ($return_value !== 0) {
            throw new \Exception("setup:mysql-init failed:\n" . implode("\n", $output));
        }
    }
}
