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

use Tuleap\REST\AuthenticatedResource;
use Tuleap\REST\Header;
use Luracast\Restler\RestException;
use UserManager;
use FRSPackageFactory;

class PackageResource extends AuthenticatedResource
{
    private $package_factory;

    public function __construct()
    {
        parent::__construct();
        $this->package_factory = FRSPackageFactory::instance();
    }

    /**
     * Get FRS package
     *
     * @url GET {id}
     * @access hybrid
     *
     * @param int    $id            ID of the package
     *
     * @return \Tuleap\FRS\REST\v1\PackageRepresentation
     */
    public function getId($id)
    {
        $package = $this->package_factory->getFRSPackageFromDb($id);

        if (! $package) {
            throw new RestException(404, "Package not found");
        }

        $user = UserManager::instance()->getCurrentUser();

        if (! $this->package_factory->userCanRead($package->getGroupID(), $package->getPackageID(), $user->getId())) {
            throw new RestException(403, "Access to package denied");
        }

        if (! $package->isActive()) {
            throw new RestException(403, "Package is not active");
        }

        $representation = new PackageRepresentation();
        $representation->build($package);

        return $representation;
    }



    /**
     * @url OPTION {id}
     */
    public function options()
    {
        Header::allowOptionsGet();
    }
}
