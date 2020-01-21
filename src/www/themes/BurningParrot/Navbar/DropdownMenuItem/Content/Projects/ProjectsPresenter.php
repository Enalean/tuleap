<?php
/**
 * Copyright (c) Enalean, 2016 - Present. All Rights Reserved.
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

namespace Tuleap\Theme\BurningParrot\Navbar\DropdownMenuItem\Content\Projects;

use ForgeConfig;
use Tuleap\Theme\BurningParrot\Navbar\DropdownMenuItem\Content\Presenter;

class ProjectsPresenter extends Presenter
{
    public $is_projects = true;

    /** @var ProjectPresenter[] */
    public $projects;
    public $are_restricted_users_allowed;
    public $is_member_of_at_least_one_project;
    public $browse_all;
    public $add_project;
    public $is_there_something_to_filter;
    public $filter;
    public $is_project_registration_enabled;
    public $is_trove_cat_enabled;
    /**
     * @var string
     */
    public $project_registration_url;

    public function __construct(
        $id,
        bool $is_project_registration_enabled,
        array $projects
    ) {
        parent::__construct($id);

        $this->are_restricted_users_allowed = ForgeConfig::areRestrictedUsersAllowed();

        $this->projects    = $projects;
        $this->browse_all  = $GLOBALS['Language']->getText('include_menu', 'browse_all');
        $this->add_project = $GLOBALS['Language']->getText('include_menu', 'add_project');
        $this->filter      = $GLOBALS['Language']->getText('include_menu', 'filter_projects');

        $this->project_registration_url = '/project/register.php';
        if (ForgeConfig::get(\ProjectManager::FORCE_NEW_PROJECT_CREATION_USAGE) === '1') {
            $this->project_registration_url = '/project/new';
        }

        $this->is_trove_cat_enabled              = ForgeConfig::get('sys_use_trove');
        $this->is_member_of_at_least_one_project = count($this->projects) > 0;
        $this->is_project_registration_enabled   = $is_project_registration_enabled;
        $this->is_there_something_to_filter      = $this->is_member_of_at_least_one_project
            || $this->is_trove_cat_enabled;
    }
}
