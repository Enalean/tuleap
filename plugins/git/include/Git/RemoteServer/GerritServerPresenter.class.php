<?php
/**
 * Copyright (c) Enalean, 2014 - Present. All Rights Reserved.
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

class Git_RemoteServer_GerritServerPresenter
{

    public $id;
    public $host;
    public $http_port;
    public $ssh_port;
    public $replication_key;
    public $use_ssl;
    public $login;
    public $identity_file;
    public $use_gerrit_2_5;
    public $is_used;
    public $http_password;
    public $replication_password;
    public $replication_key_ellipsis_value;
    public $edit_title;
    public $delete_title;
    public $purified_delete_desc;

    public function __construct(Git_RemoteServer_GerritServer $server, bool $is_used)
    {
        $this->id                             = $server->getId();
        $this->host                           = $server->getHost();
        $this->http_port                      = $server->getHTTPPort() ? $server->getHTTPPort() : '';
        $this->ssh_port                       = $server->getSSHPort();
        $this->replication_key                = $server->getReplicationKey();
        $this->use_ssl                        = $server->usesSSL();
        $this->login                          = $server->getLogin();
        $this->identity_file                  = $server->getIdentityFile();
        $this->use_gerrit_2_5                 = $server->getGerritVersion() === Git_RemoteServer_GerritServer::GERRIT_VERSION_2_5;
        $this->is_used                        = $is_used;
        $this->http_password                  = $server->getHTTPPassword();
        $this->replication_password           = $server->getReplicationPassword();
        $this->replication_key_ellipsis_value = substr($this->replication_key, 0, 40) . '...' . substr($this->replication_key, -40);

        $this->edit_title           = sprintf(dgettext('tuleap-git', 'Edit %1$s'), $server->getHost());
        $this->delete_title         = sprintf(dgettext('tuleap-git', 'Delete %1$s'), $server->getHost());

        $this->purified_delete_desc = Codendi_HTMLPurifier::instance()->purify(
            sprintf(dgettext('tuleap-git', 'Wow, wait a minute. You are about to delete the <b>%1$s</b> server. Please confirm your action.'), $server->getHost()),
            CODENDI_PURIFIER_LIGHT
        );
    }

    public function use_ssl_checked()
    {
        return $this->use_ssl ? 'checked' : '';
    }
}
