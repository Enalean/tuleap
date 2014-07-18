<?php
/**
 * Copyright (c) Enalean, 2014. All Rights Reserved.
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

use Tuleap\Project\REST\ProjectResourceReference;
use Tuleap\Testing\REST\v1\CampaignRepresentation;

/**
 * Inject resource into restler
 */
class Testing_REST_ResourcesInjector {

    public function populate(Luracast\Restler\Restler $restler) {
        $restler->addAPIClass('\\Tuleap\\Testing\\REST\\v1\\ProjectResource', 'projects');
        $restler->addAPIClass('\\Tuleap\\Testing\\REST\\v1\\CampaignsResource', 'testing_campaigns');
        $restler->addAPIClass('\\Tuleap\\Testing\\REST\\v1\\DefinitionsResource', 'testing_definitions');
    }

    public function declareProjectResource(array &$resources, Project $project) {
        $routes = array(
            CampaignRepresentation::ROUTE
        );
        foreach ($routes as $route) {
            $resource_reference = new ProjectResourceReference();
            $resource_reference->build($project, $route);

            $resources[] = $resource_reference;
        }
    }
}
