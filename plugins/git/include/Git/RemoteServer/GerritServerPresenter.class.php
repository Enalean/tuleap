<?php
/**
 * Copyright (c) Enalean, 2014 - 2016. All Rights Reserved.
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

    public $warning_no_possible_go_back;

    public function __construct(Git_RemoteServer_GerritServer $server, $is_used)
    {
        $this->id                             = $server->getId();
        $this->host                           = $server->getHost();
        $this->http_port                      = $server->getHTTPPort() ? $server->getHTTPPort() : '';
        $this->ssh_port                       = $server->getSSHPort();
        $this->replication_key                = $server->getReplicationKey();
        $this->use_ssl                        = $server->usesSSL();
        $this->login                          = $server->getLogin();
        $this->identity_file                  = $server->getIdentityFile();
        $this->use_gerrit_2_5                 = $server->getGerritVersion() === Git_RemoteServer_GerritServer::DEFAULT_GERRIT_VERSION;
        $this->use_gerrit_2_8                 = $server->getGerritVersion() !== Git_RemoteServer_GerritServer::DEFAULT_GERRIT_VERSION;
        $this->is_used                        = $is_used;
        $this->http_password                  = $server->getHTTPPassword();
        $this->replication_password           = $server->getReplicationPassword();
        $this->is_digest                      = $server->getAuthType() === Git_RemoteServer_GerritServer::AUTH_TYPE_DIGEST;
        $this->is_basic                       = $server->getAuthType() === Git_RemoteServer_GerritServer::AUTH_TYPE_BASIC;
        $this->replication_key_ellipsis_value = substr($this->replication_key, 0, 40).'...'.substr($this->replication_key, -40);


        $this->edit_title           = $GLOBALS['Language']->getText('plugin_git', 'edit_gerrit_title', $server->getHost());
        $this->delete_title         = $GLOBALS['Language']->getText('plugin_git', 'delete_gerrit_title', $server->getHost());

        $this->warning_no_possible_go_back  = $GLOBALS['Language']->getText('plugin_git', 'warning_no_possible_go_back');
        $this->purified_delete_desc = Codendi_HTMLPurifier::instance()->purify(
            $GLOBALS['Language']->getText('plugin_git', 'delete_gerrit_desc', $server->getHost()),
            CODENDI_PURIFIER_LIGHT
        );
    }

    public function gerrit_version_2_5_checked()
    {
        return $this->use_gerrit_2_5 ? 'checked' : '';
    }

    public function gerrit_version_2_8_checked()
    {
        return $this->use_gerrit_2_8 ? 'checked' : '';
    }

    public function auth_type_digest_checked()
    {
        return $this->is_digest ? 'checked' : '';
    }

    public function auth_type_basic_checked()
    {
        return $this->is_basic ? 'checked' : '';
    }

    public function use_ssl_checked()
    {
        return $this->use_ssl ? 'checked' : '';
    }
}
