<?php
/**
 * Copyright (c) Enalean, 2012 - 2016. All Rights Reserved.
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

class Git_AdminGerritPresenter extends Git_AdminPresenter {

    public $manage_gerrit = true;

    public $gerrit_active = 'tlp-tab-active';

    public $list_of_servers;

    public $add_server;

    public $list_of_servers_empty_message;

    public $gerrit_add_server;

    public $gerrit_label_host;

    public $gerrit_label_http_port;

    public $gerrit_label_ssh_port;

    public $gerrit_label_replication_key;

    public $gerrit_label_use_ssl;

    public $gerrit_label_login;

    public $gerrit_label_identity_file;

    public $gerrit_label_gerrit_version;

    public $gerrit_label_http_password;

    public $gerrit_label_replication_password;

    public $gerrit_label_auth_type;

    public function __construct($title, CSRFSynchronizerToken $csrf_token, array $list_of_gerrits) {
        parent::__construct($title, $csrf_token);

        $this->list_of_servers               = $list_of_gerrits;
        $this->add_server                    = $GLOBALS['Language']->getText('plugin_git', 'add_server');
        $this->gerrit_add_server             = $GLOBALS['Language']->getText('plugin_git', 'gerrit_add_server');
        $this->list_of_servers_empty_message = $GLOBALS['Language']->getText('plugin_git', 'gerrit_no_servers');

        $this->gerrit_label_host                 = $GLOBALS['Language']->getText('plugin_git', 'gerrit_label_host');
        $this->gerrit_label_http_port            = $GLOBALS['Language']->getText('plugin_git', 'gerrit_label_http_port');
        $this->gerrit_label_ssh_port             = $GLOBALS['Language']->getText('plugin_git', 'gerrit_label_ssh_port');
        $this->gerrit_label_replication_key      = $GLOBALS['Language']->getText('plugin_git', 'gerrit_label_replication_key');
        $this->gerrit_label_use_ssl              = $GLOBALS['Language']->getText('plugin_git', 'gerrit_label_use_ssl');
        $this->gerrit_label_login                = $GLOBALS['Language']->getText('plugin_git', 'gerrit_label_login');
        $this->gerrit_label_identity_file        = $GLOBALS['Language']->getText('plugin_git', 'gerrit_label_identity_file');
        $this->gerrit_label_gerrit_version       = $GLOBALS['Language']->getText('plugin_git', 'gerrit_label_gerrit_version');
        $this->gerrit_label_http_password        = $GLOBALS['Language']->getText('plugin_git', 'gerrit_label_http_password');
        $this->gerrit_label_replication_password = $GLOBALS['Language']->getText('plugin_git', 'gerrit_label_replication_password');
        $this->gerrit_label_auth_type            = $GLOBALS['Language']->getText('plugin_git', 'gerrit_label_auth_type');
    }

    public function list_of_servers_is_empty()
    {
        return $this->list_of_servers[0]->id === 0;
    }
}
