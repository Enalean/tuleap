<?php
/**
 * Copyright (c) Enalean, 2012 - Present. All Rights Reserved.
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
/**
 * @see Git_Driver_Gerrit_RemoteSSHConfig
 */
class Git_RemoteServer_GerritServer implements Git_Driver_Gerrit_RemoteSSHConfig
{

    public const DEFAULT_HTTP_PORT       = 80;
    public const DEFAULT_GERRIT_USERNAME = 'gerrit_username';
    public const GERRIT_VERSION_2_5      = '2.5';
    public const GERRIT_VERSION_2_8_PLUS = '2.8+';
    public const GENERIC_USER_PREFIX     = 'gerrit_';

    private $id;
    private $host;
    private $ssh_port;
    private $http_port;
    private $login;
    private $identity_file;
    private $replication_key;
    /** @var bool */
    private $use_ssl;
    /** @var String */
    private $http_password;
    /** @var String */
    private $gerrit_version;
    /** @var String */
    private $replication_password;

    public function __construct(
        $id,
        $host,
        $ssh_port,
        $http_port,
        $login,
        $identity_file,
        $replication_key,
        $use_ssl,
        $gerrit_version,
        $http_password,
        $replication_password
    ) {
        $this->id                   = $id;
        $this->host                 = $host;
        $this->ssh_port             = $ssh_port;
        $this->http_port            = $http_port;
        $this->login                = $login;
        $this->identity_file        = $identity_file;
        $this->replication_key      = $replication_key;
        $this->use_ssl              = $use_ssl;
        $this->http_password        = $http_password;
        $this->replication_password = $replication_password;
        $this->gerrit_version       = $gerrit_version;
    }

    public function __toString()
    {
        return self::class . '#' . $this->id;
    }

    public function getId()
    {
        return $this->id;
    }

    public function getHost()
    {
        return $this->host;
    }

    public function getIdentityFile()
    {
        return $this->identity_file;
    }

    public function getLogin()
    {
        return $this->login;
    }

    public function getSSHPort()
    {
        return $this->ssh_port;
    }

    public function getHTTPPort()
    {
        return $this->http_port;
    }

    public function usesSSL()
    {
        return $this->use_ssl;
    }

    public function setId($id)
    {
        $this->id = $id;
    }

    public function setHost($host)
    {
        $this->host = $host;
        return $this;
    }

    public function setIdentityFile($identity_file)
    {
        $this->identity_file = $identity_file;
        return $this;
    }

    public function setLogin($login)
    {
        $this->login = $login;
        return $this;
    }

    public function setSSHPort($ssh_port)
    {
        $this->ssh_port = $ssh_port;
        return $this;
    }

    public function setHTTPPort($http_port)
    {
        $this->http_port = $http_port;
        return $this;
    }

    public function setUseSSL($use_ssl)
    {
        $this->use_ssl = $use_ssl;
        return $this;
    }

    public function getCloneSSHUrl($gerrit_project)
    {
        return "ext::ssh -p $this->ssh_port -i $this->identity_file $this->login@$this->host %S $gerrit_project";
    }

    public function getEndUserCloneUrl($gerrit_project, ?Git_Driver_Gerrit_User $user = null)
    {
        $login = self::DEFAULT_GERRIT_USERNAME;
        if ($user !== null) {
            $login = $user->getSSHUserName();
        }
        return 'ssh://' . $login . '@' . $this->host . ':' . $this->ssh_port . '/' . $gerrit_project . '.git';
    }

    public function getProjectAdminUrl($gerrit_project)
    {
        return $this->getBaseUrl() . "/#/admin/projects/$gerrit_project";
    }

    public function getProjectUrl($gerrit_project)
    {
        return $this->getBaseUrl() . "/#/q/project:$gerrit_project,n,z";
    }

    /**
     *
     * @return String
     */
    public function getReplicationKey()
    {
        return $this->replication_key;
    }

    /**
     *
     * @param String $key
     * @return Git_RemoteServer_GerritServer
     */
    public function setReplicationKey($key)
    {
        $this->replication_key = $key;
        return $this;
    }

    /**
     * @return string The base url of the server. Eg: http://gerrit.example.com:8080/
     */
    public function getBaseUrl()
    {
        $url = $this->getHTTPProtocol() . $this->host;
        if ($this->http_port != self::DEFAULT_HTTP_PORT) {
            $url .= ":$this->http_port";
        }
        return $url;
    }

    private function getHTTPProtocol()
    {
        if ($this->usesSSL()) {
            return 'https://';
        }

        return 'http://';
    }

    /**
     * @return String
     */
    public function getGerritVersion()
    {
        return $this->gerrit_version;
    }

    /**
     * @param String $gerrit_version
     */
    public function setGerritVersion($gerrit_version)
    {
        $this->gerrit_version = $gerrit_version;
        return $this;
    }

    /**
     * @return String
     */
    public function getHTTPPassword()
    {
        return (string) $this->http_password;
    }

    /**
     * @return String
     */
    public function getReplicationPassword()
    {
        return (string) $this->replication_password;
    }

    /**
     * @param String $http_password
     */
    public function setHTTPPassword($http_password)
    {
        $this->http_password = $http_password;
        return $this;
    }

    /**
     * @param string $replication_password
     */
    public function setReplicationPassword($replication_password)
    {
        $this->replication_password = $replication_password;
        return $this;
    }

    public function getGenericUserName()
    {
        return Rule_UserName::RESERVED_PREFIX . self::GENERIC_USER_PREFIX . $this->getId();
    }
}
