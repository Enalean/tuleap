<?php
/**
 * Copyright (c) Enalean, 2016. All Rights Reserved.
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
use FRSReleaseFactory;
use Luracast\Restler\RestException;
use UserManager;
use Tuleap\FRS\Link\Retriever;
use Tuleap\FRS\Link\Dao;

class ReleaseResource extends AuthenticatedResource
{
    private $frs_release_factory;
    private $retriever;

    public function __construct()
    {
        parent::__construct();
        $this->frs_release_factory = FRSReleaseFactory::instance();
        $this->retriever           = new Retriever(new Dao());
    }
    /**
     * Get FRS release
     *
     * @url GET {id}
     * @access hybrid
     *
     * @param int    $id            ID of the release
     *
     * @return \Tuleap\FRS\REST\v1\ReleaseRepresentation
     */
    public function getId($id)
    {
        $release = $this->frs_release_factory->getFRSReleaseFromDb($id);

        if (! $release) {
            throw new RestException(404, "Release not found");
        }

        $release_representation = new ReleaseRepresentation();
        $user                   = UserManager::instance()->getCurrentUser();
        $package                = $release->getPackage();

        if (! $this->frs_release_factory->userCanRead($package->getGroupID(), $package->getPackageID(), $release->getReleaseID(), $user->getId())) {
            throw new RestException(403, "Access to release denied");
        }

        if ($package->isActive()) {
            $release_representation->build($release, $this->retriever, $user);
        } else if ($package->isHidden()
            && $this->frs_release_factory->userCanAdmin($user, $package->getGroupID())
        ) {
            $release_representation->build($release, $this->retriever, $user);
        } else {
            throw new RestException(403, "Access to package denied");
        }

        return $release_representation;
    }



    /**
     * @url OPTION {id}
     */
    public function options()
    {
        Header::allowOptionsGet();
    }
}
