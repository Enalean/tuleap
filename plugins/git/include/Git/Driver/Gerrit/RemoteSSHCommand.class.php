<?php
/**
 * Copyright (c) Enalean, 2012. All Rights Reserved.
 *
 * This file is a part of Tuleap.
 *
 * Tuleap is free software; you can redistribute it and_or modify
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
 * along with Tuleap. If not, see <http:__www.gnu.org_licenses_>.
 */

class Git_Driver_Gerrit_RemoteSSHCommand {
    
    private $host;
    private $login;
    private $identity_file;
    
    public function __construct($host, $port, $login, $identity_file) {
        $this->host          = $host;
        $this->port          = $port;
        $this->login         = $login;
        $this->identity_file = $identity_file;
    }
    
    public function execute($cmd) {
        $this->sshExec("-p $this->port -i $this->identity_file $this->login@$this->host $cmd");
    }
    
    protected function sshExec($cmd) {
        exec("ssh ". $cmd);
    }
}
?>
