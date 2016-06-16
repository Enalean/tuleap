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

namespace Tuleap\Theme\BurningParrot\Navbar\Project;

use HTTPRequest;
use PFUser;
use Project;
use ProjectManager;

class ProjectPresenterBuilder
{
    /** @var PFUser */
    private $current_user;

    /** @var ProjectManager */
    private $project_manager;

    /** @var Project[] */
    private $projects;

    public function build(
        PFUser $current_user
    ) {
        $this->current_user    = $current_user;
        $this->project_manager = ProjectManager::instance();
        $this->projects        = $this->project_manager->getActiveProjectsForUser($this->current_user);

        return $this->getProjectsPresenter();
    }

    private function getProjectsPresenter()
    {
        $projects_presenter = array();

        foreach ($this->projects as $project) {
            $projects_presenter[] = $this->getProjectPresenter($project);
        }

        return $projects_presenter;
    }

    private function getProjectPresenter(Project $project)
    {
        $project_id         = $project->getID();
        $project_name       = util_unconvert_htmlspecialchars($project->getPublicName());
        $project_uri        = '/projects/' . $project->getUnixName();
        $project_config_uri = '/project/admin/?group_id=' . $project_id;
        $is_private         = $this->getProjectIsPrivate($project);
        $user_administers   = $this->current_user->isAdmin($project_id);
        $user_belongs       = true;

        return new ProjectPresenter(
            $project_name,
            $project_uri,
            $project_config_uri,
            $is_private,
            $user_administers,
            $user_belongs
        );
    }

    private function getProjectIsPrivate(Project $project)
    {
        return $project->getAccess() === 'private';
    }
}
