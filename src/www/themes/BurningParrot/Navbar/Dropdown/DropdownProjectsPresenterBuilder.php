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

class DropdownProjectsPresenterBuilder
{
    /** @var ProjectPresenter[] */
    private $projects;

    public function build(
        array $projects
    ) {
        $this->projects = $projects;

        return $this->getDropdownProjectPresenter();
    }

    private function getDropdownProjectPresenter()
    {
        $projects_user_admins  = array();
        $projects_user_belongs = array();
        foreach ($this->projects as $project) {
            if ($project->user_administers) {
                $projects_user_admins[] = $project;
            } else if ($project->user_belongs) {
                $projects_user_belongs[] = $project;
            }
        }

        return new DropdownProjectsPresenter(
            'projects',
            $projects_user_admins,
            $projects_user_belongs,
            count($projects_user_admins) > 0,
            count($projects_user_belongs) > 0
        );
    }
}
