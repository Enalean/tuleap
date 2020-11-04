<?php
/**
 * Copyright (c) Enalean, 2020-Present. All Rights Reserved.
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

namespace Tuleap\Gitlab\REST;

use Project;
use Tuleap\Gitlab\REST\v1\GitlabProjectResource;
use Tuleap\Gitlab\REST\v1\GitlabRepositoryRepresentation;
use Tuleap\Gitlab\REST\v1\GitlabRepositoryResource;
use Tuleap\Project\REST\ProjectRepresentation;
use Tuleap\Project\REST\ProjectResourceReference;

/**
 * Inject resource into restler
 */
class ResourcesInjector
{

    public function populate(\Luracast\Restler\Restler $restler): void
    {
        $restler->addAPIClass(GitlabProjectResource::class, ProjectRepresentation::ROUTE);
        $restler->addAPIClass(GitlabRepositoryResource::class, GitlabRepositoryRepresentation::ROUTE);
    }

    public function declareProjectGitlabResource(array &$resources, Project $project): void
    {
        $routes = [
            GitlabRepositoryRepresentation::ROUTE
        ];

        foreach ($routes as $route) {
            $resource_reference = new ProjectResourceReference();
            $resource_reference->build($project, $route);

            $resources[] = $resource_reference;
        }
    }
}
