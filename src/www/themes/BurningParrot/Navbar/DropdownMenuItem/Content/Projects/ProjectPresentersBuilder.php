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

use PFUser;
use Project;
use ProjectManager;

class ProjectPresentersBuilder
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

        return $this->getProjectPresenters();
    }

    private function getProjectPresenters()
    {
        $project_presenters = array();

        foreach ($this->projects as $project) {
            $project_presenters[] = $this->getProjectPresenter($project);
        }

        return $project_presenters;
    }

    private function getProjectPresenter(Project $project)
    {
        $project_id         = $project->getID();
        $project_name       = $project->getPublicName();
        $project_config_uri = '/project/admin/?group_id=' . $project_id;
        $user_administers   = $this->current_user->isAdmin($project_id);
        $user_belongs       = true;

        return new ProjectPresenter(
            $project_name,
            $project->getUrl(),
            $project_config_uri,
            $user_administers,
            $user_belongs,
            $project
        );
    }
}
