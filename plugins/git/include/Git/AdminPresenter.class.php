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

class Git_AdminPresenter
{

    public $title;

    public $csrf_token;

    public $manage_gerrit                         = false;
    public $manage_mirrors                        = false;
    public $manage_gitolite_config                = false;
    public $manage_general_settings               = false;

    public $mirrors_active          = '';
    public $general_settings_active = '';
    public $gerrit_active           = '';
    public $gitolite_config_active  = '';

    public function __construct($title, CSRFSynchronizerToken $csrf_token)
    {
        $this->title      = $title;
        $this->csrf_token = $csrf_token;
    }

    public function gerrit_tab_name()
    {
        return dgettext('tuleap-git', 'Gerrit');
    }

    public function general_settings_tab_name()
    {
        return dgettext('tuleap-git', 'General settings');
    }

    public function mirror_tab_name()
    {
        return dgettext('tuleap-git', 'Mirrors');
    }

    public function gitolite_config_tab_name()
    {
        return dgettext('tuleap-git', 'Gitolite');
    }
}
