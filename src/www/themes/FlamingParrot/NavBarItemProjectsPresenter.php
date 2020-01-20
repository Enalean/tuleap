<?php
/**
 * Copyright (c) Enalean, 2016. All Rights Reserved.
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

class FlamingParrot_NavBarItemProjectsPresenter extends FlamingParrot_NavBarItemPresenter
{

    public $is_projects = true;

    public $label;
    public $projects;
    public $has_projects;
    public $is_trove_cat_enabled;
    public $is_project_registration_enabled;
    public $display_only_trovemap;
    public $display_dropdown;
    public $filter_project;
    public $menu_projects_text;
    public $register_new_proj;
    public $browse_projects_text;
    /**
     * @var string
     */
    public $project_registration_url;

    public function __construct($id, $is_active, bool $is_project_registration_enabled, PFUser $user, array $projects)
    {
        parent::__construct($id, $is_active);

        $this->projects     = $projects;
        $this->has_projects = count($projects) > 0;

        $this->label                = $GLOBALS['Language']->getText('include_menu', 'projects');
        $this->filter_project       = $GLOBALS['Language']->getText('include_menu', 'filter_project');
        $this->menu_projects_text   = $GLOBALS['Language']->getText('include_menu', 'projects');
        $this->browse_projects_text = $GLOBALS['Language']->getText('include_menu', 'browse_projects');
        $this->register_new_proj    = $GLOBALS['Language']->getText('include_menu', 'register_new_proj');

        $this->is_trove_cat_enabled            = ForgeConfig::get('sys_use_trove');
        $this->is_project_registration_enabled = $is_project_registration_enabled;

        $this->project_registration_url = '/project/register.php';
        if (ForgeConfig::get(\ProjectManager::FORCE_NEW_PROJECT_CREATION_USAGE) === '1') {
            $this->project_registration_url = '/project/new';
        }

        $this->display_only_trovemap =
            $this->is_trove_cat_enabled
            && ! $this->is_project_registration_enabled
            && ! $this->projects;
        $this->display_dropdown      = $user->isLoggedIn() &&
            ($this->has_projects || $this->is_trove_cat_enabled || $this->is_project_registration_enabled);
    }
}
