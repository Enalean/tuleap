<?php
/**
 * Copyright (c) Enalean, 2014. All rights reserved
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

class GitPresenters_AdminMassUpdatePresenter extends GitPresenters_AdminPresenter {

    /**
     * @var array
     */
    public $repositories;


    public function __construct($project_id, array $repositories) {
        parent::__construct($project_id);

        $this->manage_mass_update = true;
        $this->repositories       = $repositories;
    }

    public function title() {
        return $GLOBALS['Language']->getText('plugin_git', 'view_admin_mass_update_title');
    }

    public function select_repositories() {
        return $GLOBALS['Language']->getText('plugin_git', 'view_admin_mass_update_select_repositories');
    }

    public function repository_list_name() {
        return $GLOBALS['Language']->getText('plugin_git', 'view_admin_mass_update_repository_list_name');
    }
}