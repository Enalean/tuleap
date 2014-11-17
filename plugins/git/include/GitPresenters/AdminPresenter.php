<?php
/**
 * Copyright (c) Enalean, 2013. All rights reserved
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/
 */

class GitPresenters_AdminPresenter {

    public $project_id;

    public $manage_gerrit_templates = false;

    public $manage_git_admins = false;

    public $manage_mass_update = false;


    public function __construct($project_id) {
        $this->project_id = $project_id;
    }

    public function git_admin() {
        return $GLOBALS['Language']->getText('plugin_git', 'view_admin_title');
    }

    public function tab_gerrit_templates() {
        return $GLOBALS['Language']->getText('plugin_git', 'view_admin_tab_gerrit_templates');
    }

    public function tab_git_admins() {
        return $GLOBALS['Language']->getText('plugin_git', 'view_admin_tab_git_admins');
    }

    public function tab_mass_update() {
        return $GLOBALS['Language']->getText('plugin_git', 'view_admin_tab_mass_update');
    }
}
