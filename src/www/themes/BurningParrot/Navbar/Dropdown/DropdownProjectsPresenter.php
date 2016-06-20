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

namespace Tuleap\Theme\BurningParrot\Navbar\Dropdown;

use Tuleap\Theme\BurningParrot\Navbar\Project\ProjectPresenter;

class DropdownProjectsPresenter extends DropdownPresenter
{
    public $is_projects = true;

    /** @var ProjectPresenter[] */
    public $projects_user_admins;

    /** @var ProjectPresenter[] */
    public $projects_user_belongs;

    /** @var boolean */
    public $has_projects_user_admins;

    /** @var boolean */
    public $has_projects_user_belongs;

    public function __construct(
        $id,
        array $projects_user_admins,
        array $projects_user_belongs,
        $has_projects_user_admins,
        $has_projects_user_belongs
    ) {
        parent::__construct($id);

        $this->projects_user_admins      = $projects_user_admins;
        $this->projects_user_belongs     = $projects_user_belongs;
        $this->has_projects_user_admins  = $has_projects_user_admins;
        $this->has_projects_user_belongs = $has_projects_user_belongs;
    }

    public function projects_user_admins_title()
    {
        return $GLOBALS['Language']->getText('include_menu', 'projects_user_admin');
    }

    public function projects_user_belongs_title()
    {
        return $GLOBALS['Language']->getText('include_menu', 'projects_user_belong');
    }

    public function browse_all()
    {
        return $GLOBALS['Language']->getText('include_menu', 'browse_all');
    }

    public function add_project()
    {
        return $GLOBALS['Language']->getText('include_menu', 'add_project');
    }

    public function filter()
    {
        return $GLOBALS['Language']->getText('include_menu', 'filter_projects');
    }
}
