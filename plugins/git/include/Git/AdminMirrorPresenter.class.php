<?php
/**
 * Copyright (c) Enalean, 2012 - 2014. All Rights Reserved.
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

abstract class Git_AdminMirrorPresenter extends Git_AdminPresenter {

    public $manage_mirrors = true;

    public $mirrors_active = 'tlp-tab-active';

    public $add_a_mirror = false;

    public $modify_a_mirror = false;

    public $see_all = false;

    public function __construct($title, CSRFSynchronizerToken $csrf) {
        parent::__construct($title, $csrf);
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

    public function identifier() {
        return $GLOBALS['Language']->getText('plugin_git', 'identifier');
    }

    public function identifier_placeholder() {
        return $GLOBALS['Language']->getText('plugin_git', 'identifier_placeholder');
    }

    public function url_placeholder() {
        return $GLOBALS['Language']->getText('plugin_git', 'url_placeholder');
    }

    public function hostname_placeholder() {
        return $GLOBALS['Language']->getText('plugin_git', 'hostname_placeholder');
    }

    public function reserved_hostnames_help() {
        return $GLOBALS['Language']->getText('plugin_git', 'reserved_hostnames_help');
    }
}
