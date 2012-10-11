<?php

/**
 * Copyright (c) Enalean, 2012. All Rights Reserved.
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

require_once GIT_BASE_DIR. '/Git/Driver/Gerrit/RemoteSSHConfig.class.php';
class Git_RemoteServer_GerritServer implements Git_Driver_Gerrit_RemoteSSHConfig {

    
    private $host;
    private $port;
    private $login;
    private $identity_file;
    
    public function __construct($host, $port, $login, $identity_file) {
        $this->host          = $host;
        $this->port          = $port;
        $this->login         = $login;
        $this->identity_file = $identity_file;
    }

    public function getHost() {
        return $this->host;
    }

    public function getIdentityFile() {
        return $this->identity_file;
    }

    public function getLogin() {
        return $this->login;
    }

    public function getPort() {
        return $this->port;
    }

}

?>
