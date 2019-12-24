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

class Git_AdminGerritPresenter extends Git_AdminPresenter
{
    public $manage_gerrit = true;

    public $gerrit_active = 'tlp-tab-active';

    public $list_of_servers;

    public $add_server;

    public $list_of_servers_empty_message;

    public $btn_edit;

    public $btn_delete;

    public $btn_cancel;

    public $empty_content;

    public $not_specified_host;

    public $gerrit_add_server;

    public $gerrit_label_host;

    public $gerrit_label_http_port;

    public $gerrit_label_ssh_port;

    public $gerrit_label_replication_key;

    public $gerrit_label_replication_key_infos;

    public $gerrit_label_use_ssl;

    public $gerrit_label_login;

    public $gerrit_label_identity_file;

    public $gerrit_label_http_password;

    public $gerrit_label_replication_password;

    public $btn_delete_title;

    public $gerrit_replication_information;

    public $gerrit_label_http_password_edit;

    public $gerrit_label_replication_password_edit;

    public function __construct($title, CSRFSynchronizerToken $csrf_token, array $list_of_gerrits)
    {
        parent::__construct($title, $csrf_token);

        $this->list_of_servers               = $list_of_gerrits;
        $this->add_server                    = dgettext('tuleap-git', 'Add server');
        $this->gerrit_add_server             = dgettext('tuleap-git', 'Add gerrit server');
        $this->list_of_servers_empty_message = dgettext('tuleap-git', 'There is nothing here,<br> start by adding a gerrit server.');
        $this->btn_edit                      = $GLOBALS['Language']->getText('global', 'btn_edit');
        $this->btn_delete                    = $GLOBALS['Language']->getText('global', 'btn_delete');
        $this->btn_cancel                    = $GLOBALS['Language']->getText('global', 'btn_cancel');
        $this->btn_delete_title              = dgettext('tuleap-git', 'This server is already used by some repositories.');
        $this->empty_content                 = dgettext('tuleap-git', 'Empty');
        $this->not_specified_host            = dgettext('tuleap-git', 'Not specified host');

        $this->gerrit_label_host                    = dgettext('tuleap-git', 'Host');
        $this->gerrit_label_http_port               = dgettext('tuleap-git', 'HTTP port');
        $this->gerrit_label_ssh_port                = dgettext('tuleap-git', 'SSH port');
        $this->gerrit_label_replication_key         = dgettext('tuleap-git', 'Replication SSH Key');
        $this->gerrit_label_replication_key_infos   = dgettext('tuleap-git', 'Replication SSH Key (SSH key of the user who runs gerrit server)');

        $this->gerrit_label_use_ssl              = dgettext('tuleap-git', 'Use SSL?');
        $this->gerrit_label_login                = dgettext('tuleap-git', 'Login');
        $this->gerrit_label_identity_file        = dgettext('tuleap-git', 'Identity file');
        $this->gerrit_label_http_password        = dgettext('tuleap-git', 'HTTP password');
        $this->gerrit_label_replication_password = dgettext('tuleap-git', 'Replication password');
        $this->gerrit_replication_information    = dgettext('tuleap-git', 'It is either SSH or password replication for replication');

        $this->gerrit_label_http_password_edit        = dgettext('tuleap-git', 'Change HTTP password');
        $this->gerrit_label_replication_password_edit = dgettext('tuleap-git', 'Change replication password');

        $this->yes            = dgettext('tuleap-git', 'Yes');
        $this->no             = dgettext('tuleap-git', 'No');
        $this->btn_restrict   = $GLOBALS['Language']->getText('global', 'btn_restrict');
    }

    public function list_of_servers_is_empty()
    {
        return count($this->list_of_servers) === 0;
    }
}
