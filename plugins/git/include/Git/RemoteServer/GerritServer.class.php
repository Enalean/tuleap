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
require_once 'common/event/EventManager.class.php';
require_once GIT_BASE_DIR. '/Git/Driver/Gerrit/RemoteSSHConfig.class.php';

/**
 * @see Git_Driver_Gerrit_RemoteSSHConfig
 */
class Git_RemoteServer_GerritServer implements Git_Driver_Gerrit_RemoteSSHConfig {

    const DEFAULT_HTTP_PORT = 80;

    private $id;
    private $host;
    private $ssh_port;
    private $http_port;
    private $login;
    private $identity_file;

    public function __construct($id, $host, $ssh_port, $http_port, $login, $identity_file) {
        $this->id            = $id;
        $this->host          = $host;
        $this->ssh_port      = $ssh_port;
        $this->http_port     = $http_port;
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

    public function getSSHPort() {
        return $this->ssh_port;
    }

    public function getHTTPPort() {
        return $this->http_port;
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

    public function setSSHPort($ssh_port) {
        $this->ssh_port = $ssh_port;
        return $this;
    }

    public function setHTTPPort($http_port) {
        $this->http_port = $http_port;
        return $this;
    }

    public function getCloneSSHUrl($gerrit_project) {
        return "ext::ssh -p $this->ssh_port -i $this->identity_file $this->login@$this->host %S $gerrit_project";
    }

    public function getEndUserCloneUrl($gerrit_project, User $user) {
        $login  = 'gerrit_username';
        $params = array('login' => &$login);
        EventManager::instance()->processEvent(Event::GET_LDAP_LOGIN_NAME_FOR_USER, $params);
        return 'ssh://'.$login.'@'.$this->host.':'.$this->ssh_port.'/'.$gerrit_project.'.git';
    }

    public function getProjectAdminUrl($gerrit_project) {
        return $this->getGerritServerBaseUrl()."/#/admin/projects/$gerrit_project";
    }

    public function getProjectUrl($gerrit_project) {
        return $this->getGerritServerBaseUrl()."/#/q/project:$gerrit_project,n,z";
    }

    private function getGerritServerBaseUrl() {
        $url = "http://$this->host";
        if ($this->http_port != self::DEFAULT_HTTP_PORT) {
            $url .= ":$this->http_port";
        }
        return $url;
    }
}
?>
