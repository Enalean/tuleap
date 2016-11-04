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

class Git_AdminMirrorListPresenter extends Git_AdminMirrorPresenter {

    const TEMPLATE = 'admin-plugin';

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

    public function __construct($title, CSRFSynchronizerToken $csrf, array $list_of_mirrors) {
        parent::__construct($title, $csrf);

        $this->list_of_mirrors                        = $list_of_mirrors;
        $this->list_of_mirrors_empty_message_part_one = $GLOBALS['Language']->getText('plugin_git', 'mirror_no_mirrors_part_one');
        $this->list_of_mirrors_empty_message_part_two = $GLOBALS['Language']->getText('plugin_git', 'mirror_no_mirrors_part_two');
        $this->empty_content                          = $GLOBALS['Language']->getText('plugin_git', 'empty_content');
        $this->no_repositories                        = $GLOBALS['Language']->getText('plugin_git', 'mirror_no_repositories');
        $this->btn_submit                             = $GLOBALS['Language']->getText('global', 'btn_submit');
        $this->btn_close                              = $GLOBALS['Language']->getText('global', 'btn_close');
        $this->mirror_repo                            = $GLOBALS['Language']->getText('plugin_git', 'mirror_repo');
        $this->base_url                               = GIT_BASE_URL;
    }

    public function getTemplate() {
        return self::TEMPLATE;
    }

    public function mirror_section_title() {
        return $GLOBALS['Language']->getText('plugin_git','mirror_section_title');
    }

    public function url_label() {
        return $GLOBALS['Language']->getText('plugin_git','url_label');
    }

    public function hostname_label() {
        return $GLOBALS['Language']->getText('plugin_git','hostname_label');
    }

    public function owner_label() {
        return $GLOBALS['Language']->getText('plugin_git','owner_label');
    }

    public function ssh_key_label() {
        return $GLOBALS['Language']->getText('plugin_git','ssh_key_label');
    }

    public function pwd_label() {
        return $GLOBALS['Language']->getText('plugin_git','pwd_label');
    }

    public function mirrored_repo_label() {
        return $GLOBALS['Language']->getText('plugin_git','mirrored_repo_label');
    }

    public function manage_allowed_projects_label() {
        return $GLOBALS['Language']->getText('plugin_git','manage_allowed_projects_label');
    }

    public function list_of_mirrors_is_empty() {
        return count($this->list_of_mirrors) === 0;
    }

    public function dump_mirrored_repositories_label() {
        return $GLOBALS['Language']->getText('plugin_git','dump_mirrored_repositories_label');
    }

    public function dump_mirrored_repositories_text() {
        return $GLOBALS['Language']->getText('plugin_git', 'dump_mirrored_repositories_text');
    }

    public function repositories_label()
    {
        return $GLOBALS['Language']->getText('plugin_git','repositories_label');
    }
}
