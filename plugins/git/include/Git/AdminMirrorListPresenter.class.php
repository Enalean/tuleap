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

class Git_AdminMirrorListPresenter extends Git_AdminMirrorPresenter
{

    public const TEMPLATE = 'admin-plugin';

    public $see_all = true;
    public $list_of_mirrors;
    public $list_of_mirrors_empty_message_part_one;
    public $list_of_mirrors_empty_message_part_two;
    public $empty_content;
    public $no_repositories;
    public $btn_submit;
    public $mirror_repo;
    public $btn_close;
    public $base_url;

    public function __construct($title, CSRFSynchronizerToken $csrf, array $list_of_mirrors)
    {
        parent::__construct($title, $csrf);

        $this->list_of_mirrors                        = $list_of_mirrors;
        $this->list_of_mirrors_empty_message_part_one = dgettext('tuleap-git', 'There is nothing here,');
        $this->list_of_mirrors_empty_message_part_two = dgettext('tuleap-git', 'start by adding a mirror.');
        $this->empty_content                          = dgettext('tuleap-git', 'Empty');
        $this->no_repositories                        = dgettext('tuleap-git', 'None');
        $this->btn_submit                             = $GLOBALS['Language']->getText('global', 'btn_submit');
        $this->btn_close                              = $GLOBALS['Language']->getText('global', 'btn_close');
        $this->mirror_repo                            = dgettext('tuleap-git', 'Repository');
        $this->base_url                               = GIT_BASE_URL;
    }

    public function getTemplate()
    {
        return self::TEMPLATE;
    }

    public function mirror_section_title()
    {
        return dgettext('tuleap-git', 'Git mirror administration');
    }

    public function url_label()
    {
        return dgettext('tuleap-git', 'SSH Host');
    }

    public function hostname_label()
    {
        return dgettext('tuleap-git', 'Hostname');
    }

    public function owner_label()
    {
        return dgettext('tuleap-git', 'Owner');
    }

    public function ssh_key_label()
    {
        return dgettext('tuleap-git', 'Owner\'s SSH Key');
    }

    public function pwd_label()
    {
        return dgettext('tuleap-git', 'Owner\'s Password');
    }

    public function mirrored_repo_label()
    {
        return dgettext('tuleap-git', 'Repositories on this mirror');
    }

    public function manage_allowed_projects_label()
    {
        return dgettext('tuleap-git', 'Manage allowed projects');
    }

    public function list_of_mirrors_is_empty()
    {
        return count($this->list_of_mirrors) === 0;
    }

    public function dump_mirrored_repositories_label()
    {
        return dgettext('tuleap-git', 'Rewrite mirrored repositories configuration');
    }

    public function dump_mirrored_repositories_text()
    {
        return dgettext('tuleap-git', 'A system event will be queued to dump the mirrored repositories configuration. This dump will rewrite the gitolite configuration according to the hostnames of each mirror and the hostname define for this server in the .gitolite.rc file. Do you want to continue?');
    }

    public function repositories_label()
    {
        return dgettext('tuleap-git', 'Repositories on this mirror');
    }
}
