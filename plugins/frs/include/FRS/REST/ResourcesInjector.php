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

namespace Tuleap\FRS\REST;

use Luracast\Restler\Restler;
use Project;
use Tuleap\FRS\REST\v1\FileRepresentation;
use Tuleap\FRS\REST\v1\FileResource;
use Tuleap\FRS\REST\v1\PackageMinimalRepresentation;
use Tuleap\FRS\REST\v1\PackageResource;
use Tuleap\FRS\REST\v1\ProjectResource;
use Tuleap\FRS\REST\v1\ReleaseRepresentation;
use Tuleap\FRS\REST\v1\ReleaseResource;
use Tuleap\FRS\REST\v1\ServiceRepresentation;
use Tuleap\Project\REST\ProjectRepresentation;
use Tuleap\Project\REST\ProjectResourceReference;

class ResourcesInjector
{
    public function populate(Restler $restler)
    {
        $restler->addAPIClass(ReleaseResource::class, ReleaseRepresentation::ROUTE);
        $restler->addAPIClass(PackageResource::class, PackageMinimalRepresentation::ROUTE);
        $restler->addAPIClass(FileResource::class, FileRepresentation::ROUTE);
        $restler->addAPIClass(ProjectResource::class, ProjectRepresentation::ROUTE);
    }

    public function declareProjectResources(array &$resources, Project $project)
    {
        if ($project->usesFile()) {
            $resources[] = (new ProjectResourceReference())->build($project, PackageMinimalRepresentation::ROUTE);
            $resources[] = (new ProjectResourceReference())->build($project, ServiceRepresentation::ROUTE);
        }
    }
}
