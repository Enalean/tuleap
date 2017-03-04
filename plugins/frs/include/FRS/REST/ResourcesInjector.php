<?php
/**
 * Copyright (c) Enalean, 2016 - 2017. All Rights Reserved.
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

namespace Tuleap\FRS\REST;

use Project;
use Tuleap\FRS\REST\v1\PackageMinimalRepresentation;
use Tuleap\FRS\REST\v1\ReleaseRepresentation;
use Tuleap\Project\REST\ProjectResourceReference;

class ResourcesInjector
{
    public function populate(\Luracast\Restler\Restler $restler)
    {
        $restler->addAPIClass('\\Tuleap\\FRS\\REST\\v1\\ReleaseResource', ReleaseRepresentation::ROUTE);
        $restler->addAPIClass('\\Tuleap\\FRS\\REST\\v1\\PackageResource', PackageMinimalRepresentation::ROUTE);
    }

    public function declareProjectResource(array &$resources, Project $project)
    {
        if (! $project->usesFile()) {
            return;
        }

        $routes = array(
            PackageMinimalRepresentation::ROUTE,
        );

        foreach ($routes as $route) {
            $resource_reference = new ProjectResourceReference();
            $resource_reference->build($project, $route);

            $resources[] = $resource_reference;
        }
    }
}
