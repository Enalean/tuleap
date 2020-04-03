<?php
/**
 * Copyright (c) Enalean, 2014-2017. All Rights Reserved.
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

namespace Tuleap\TestManagement\REST;

use Project;
use Tuleap\Project\REST\ProjectResourceReference;
use Tuleap\TestManagement\REST\v1\CampaignRepresentation;
use Tuleap\TestManagement\REST\v1\DefinitionRepresentation;
use Tuleap\TestManagement\REST\v1\NodeReferenceRepresentation;

/**
 * Inject resource into restler
 */
class ResourcesInjector
{

    public function populate(\Luracast\Restler\Restler $restler): void
    {
        $restler->addAPIClass('\\Tuleap\\TestManagement\\REST\\v1\\ProjectResource', 'projects');
        $restler->addAPIClass('\\Tuleap\\TestManagement\\REST\\v1\\CampaignsResource', 'testmanagement_campaigns');
        $restler->addAPIClass('\\Tuleap\\TestManagement\\REST\\v1\\DefinitionsResource', 'testmanagement_definitions');
        $restler->addAPIClass('\\Tuleap\\TestManagement\\REST\\v1\\ExecutionsResource', 'testmanagement_executions');
        $restler->addAPIClass('\\Tuleap\\TestManagement\\REST\\v1\\NodeResource', 'testmanagement_nodes');
    }

    public function declareProjectResource(array &$resources, Project $project): void
    {
        $routes = array(
            CampaignRepresentation::ROUTE,
            DefinitionRepresentation::ROUTE,
            NodeReferenceRepresentation::ROUTE,
        );
        foreach ($routes as $route) {
            $resource_reference = new ProjectResourceReference();
            $resource_reference->build($project, $route);

            $resources[] = $resource_reference;
        }
    }
}
