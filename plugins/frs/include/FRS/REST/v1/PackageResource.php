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

use FRSReleaseFactory;
use ProjectManager;
use Tuleap\FRS\Link\Dao;
use Tuleap\FRS\Link\Retriever;
use Tuleap\REST\AuthenticatedResource;
use Tuleap\REST\Header;
use Luracast\Restler\RestException;
use UserManager;
use FRSPackageFactory;

class PackageResource extends AuthenticatedResource
{
    const MAX_LIMIT      = 50;
    const DEFAULT_LIMIT  = 10;
    const DEFAULT_OFFSET = 0;

    private $package_factory;
    private $project_manager;
    private $retriever;
    private $user_manager;

    public function __construct()
    {
        parent::__construct();
        $this->package_factory = FRSPackageFactory::instance();
        $this->release_factory = FRSReleaseFactory::instance();
        $this->project_manager = ProjectManager::instance();
        $this->retriever       = new Retriever(new Dao());
        $this->user_manager    = UserManager::instance();
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
        $package = $this->getPackage($id);

        $representation = new PackageRepresentation();
        $representation->build($package, $this->project_manager);
        $representation->setProject($this->project_manager->getProject($package->getGroupID()));

        $this->sendOptionsHeadersForGetId();

        return $representation;
    }

    /**
     * @url OPTION {id}
     */
    public function optionsId()
    {
        $this->sendOptionsHeadersForGetId();
    }

    /**
     * Get FRS releases
     *
     * Get the releases of a package
     *
     * @url GET {id}/frs_release
     * @access hybrid
     *
     * @param int $id     ID of the package
     * @param int $limit  Number of elements displayed per page {@from path}{@min 1}{@max 50}
     * @param int $offset Position of the first element to display {@from path}{@min 0}
     *
     * @return \Tuleap\FRS\REST\v1\ReleaseRepresentationPaginatedCollectionRepresentation
     */
    public function getReleases($id, $limit = self::DEFAULT_LIMIT, $offset = self::DEFAULT_OFFSET)
    {
        $package      = $this->getPackage($id);
        $current_user = $this->user_manager->getCurrentUser();

        $paginated_releases = $this->release_factory->getPaginatedActiveFRSReleasesForUser(
            $package,
            $current_user,
            $limit,
            $offset
        );
        $total_size = $paginated_releases->getTotalSize();

        $releases = array();
        foreach ($paginated_releases->getReleases() as $release) {
            $representation = new ReleaseRepresentation();
            $representation->build($release, $this->retriever, $current_user);

            $releases[] = $representation;
        }

        $this->sendOptionsHeadersForReleases();
        $this->sendPaginationHeaders($limit, $offset, $total_size);

        $collection = new ReleaseRepresentationPaginatedCollectionRepresentation();
        $collection->build($releases, $total_size);

        return $collection;
    }

    /**
     * @url OPTION {id}/frs_releases
     */
    public function optionsReleases()
    {
        $this->sendOptionsHeadersForReleases();
    }

    /**
     * @return \FRSPackage
     */
    private function getPackage($id)
    {
        $package = $this->package_factory->getFRSPackageFromDb($id);

        if (!$package) {
            throw new RestException(404, "Package not found");
        }

        $user = $this->user_manager->getCurrentUser();

        if (!$this->package_factory->userCanRead($package->getGroupID(), $package->getPackageID(), $user->getId())) {
            throw new RestException(403, "Access to package denied");
        }

        if (!$package->isActive()) {
            throw new RestException(403, "Package is not active");
        }

        return $package;
    }

    private function sendOptionsHeadersForGetId()
    {
        Header::allowOptionsGet();
    }

    private function sendOptionsHeadersForReleases()
    {
        Header::allowOptionsGet();
    }

    private function sendPaginationHeaders($limit, $offset, $size)
    {
        Header::sendPaginationHeaders($limit, $offset, $size, self::MAX_LIMIT);
    }
}
