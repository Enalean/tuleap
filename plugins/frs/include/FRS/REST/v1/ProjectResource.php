<?php
/**
 * Copyright (c) Enalean, 2017. All Rights Reserved.
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

namespace Tuleap\FRS\REST\v1;

use FRSPackageFactory;
use Project;
use Luracast\Restler\RestException;

class ProjectResource
{
    /**
     * @var FRSPackageFactory
     */
    private $package_factory;

    public function __construct(FRSPackageFactory $package_factory)
    {
        $this->package_factory = $package_factory;
    }

    public function getPackages(Project $project)
    {
        if (! $project->usesFile()) {
            throw new RestException(404, 'File Release System service is not used by the project');
        }

        $packages = array();
        foreach ($this->package_factory->getActiveFRSPackages($project->getID()) as $package) {
            $representation = new PackageRepresentation();
            $representation->build($package);

            $packages[] = $representation;
        }

        return new PackageRepresentationPaginatedCollection($packages, count($packages));
    }
}
