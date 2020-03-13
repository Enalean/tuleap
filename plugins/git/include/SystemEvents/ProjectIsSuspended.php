<?php
/**
 * Copyright (c) Enalean, 2018. All Rights Reserved.
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
 * along with Tuleap; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

namespace Tuleap\Git\SystemEvents;

use Git_GitoliteDriver;
use ProjectManager;
use SystemEvent;

class ProjectIsSuspended extends SystemEvent
{
    public const NAME = 'GIT_PROJECT_IS_SUSPENDED';

    /** @var Git_GitoliteDriver */
    private $gitolite_driver;

    /** @var ProjectManager */
    private $project_manager;

    public function injectDependencies(
        Git_GitoliteDriver $gitolite_driver,
        ProjectManager $project_manager
    ) {
        $this->gitolite_driver  = $gitolite_driver;
        $this->project_manager  = $project_manager;
    }

    public function process()
    {
        $project_id = $this->getProjectIdFromParameters();

        if (! $project_id) {
            $this->error("Missing project_id parameter");
            return false;
        }

        $project = $this->project_manager->getProject($project_id);

        if (! $project) {
            $this->error("Project does not exist");
            return false;
        }

        $this->gitolite_driver->dumpSuspendedProjectRepositoriesConfiguration($project);
        $this->done();
        return true;
    }

    private function getProjectIdFromParameters()
    {
        $parameters = $this->getParametersAsArray();

        return $parameters[0];
    }

    public function verbalizeParameters($with_link)
    {
        $project_id = $this->getProjectIdFromParameters();

        return 'Project: ' . $this->verbalizeProjectId($project_id, $with_link);
    }
}
