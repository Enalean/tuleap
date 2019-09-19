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

abstract class Git_AdminMirrorPresenter extends Git_AdminPresenter
{

    public $manage_mirrors = true;

    public $mirrors_active = 'tlp-tab-active';

    public $add_a_mirror = false;

    public $modify_a_mirror = false;

    public $see_all = false;

    public function __construct($title, CSRFSynchronizerToken $csrf)
    {
        parent::__construct($title, $csrf);
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

    public function identifier()
    {
        return dgettext('tuleap-git', 'Identifier');
    }

    public function reserved_hostnames_help()
    {
        return dgettext('tuleap-git', 'Must be unique. The name "projects" and the hostname defined in .gitolite.rc file cannot be used.');
    }

    public function add_mirror()
    {
        return dgettext('tuleap-git', 'Add mirror');
    }

    public function no_specified_mirror_name()
    {
        return dgettext('tuleap-git', 'Not specified mirror name');
    }

    public function btn_edit()
    {
        return $GLOBALS['Language']->getText('global', 'btn_edit');
    }

    public function btn_restrict()
    {
        return $GLOBALS['Language']->getText('global', 'btn_restrict');
    }

    public function btn_delete()
    {
        return $GLOBALS['Language']->getText('global', 'btn_delete');
    }

    public function btn_cancel()
    {
        return $GLOBALS['Language']->getText('global', 'btn_cancel');
    }

    public function btn_yes()
    {
        return $GLOBALS['Language']->getText('global', 'yes');
    }

    public function warning()
    {
        return $GLOBALS['Language']->getText('global', 'warning');
    }
}
