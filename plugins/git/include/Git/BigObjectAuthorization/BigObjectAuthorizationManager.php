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
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

namespace Tuleap\Git\BigObjectAuthorization;

use Project;
use ProjectManager;

class BigObjectAuthorizationManager
{
    /**
     * @var BigObjectAuthorizationDao
     */
    private $big_object_authorization_dao;

    /**
     * @var ProjectManager
     */
    private $project_manager;

    public function __construct(
        BigObjectAuthorizationDao $big_object_authorization_dao,
        ProjectManager $project_manager
    ) {
        $this->big_object_authorization_dao = $big_object_authorization_dao;
        $this->project_manager              = $project_manager;
    }

    public function authorizeProject(Project $project)
    {
        $this->big_object_authorization_dao->authorizeProject($project->getID());
    }

    public function revokeProjectAuthorization(array $project_ids)
    {
        $this->big_object_authorization_dao->revokeProjectAuthorization($project_ids);
    }

    /**
     * @return Project[]
     */
    public function getAuthorizedProjects()
    {
        $projects = [];

        foreach ($this->big_object_authorization_dao->getAuthorizedProjects() as $authorizedProject) {
            $projects[] = $this->project_manager->getProject($authorizedProject);
        }

        return $projects;
    }
}
