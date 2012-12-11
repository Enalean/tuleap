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

/**
 * @see Git_Driver_Gerrit_RemoteSSHConfig
 */
class Git_RemoteServer_GerritServer implements Git_Driver_Gerrit_RemoteSSHConfig {

    private $id;
    private $host;
    private $port;
    private $login;
    private $identity_file;

    public function __construct($id, $host, $port, $login, $identity_file) {
        $this->id            = $id;
        $this->host          = $host;
        $this->port          = $port;
        $this->login         = $login;
        $this->identity_file = $identity_file;
    }

    public function getId() {
        return $this->id;
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

    public function setHost($host) {
        $this->host = $host;
        return $this;
    }

    public function setIdentityFile($identity_file) {
        $this->identity_file = $identity_file;
        return $this;
    }

    public function setLogin($login) {
        $this->login = $login;
        return $this;
    }

    public function setPort($port) {
        $this->port = $port;
        return $this;
    }

    public function getCloneSSHUrl() {
        return "ssh://$this->host"; //because the ssh config have been correctly set up
        return "ssh://$this->login@$this->host:$this->port";
    }
}
?>
