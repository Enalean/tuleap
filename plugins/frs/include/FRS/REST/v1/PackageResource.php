<?php
/**
 * Copyright (c) Enalean, 2017 - Present. All Rights Reserved.
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
use FRSReleaseFactory;
use Luracast\Restler\RestException;
use PermissionsManager;
use ProjectManager;
use Tuleap\FRS\FRSPermissionManager;
use Tuleap\FRS\Link\Dao;
use Tuleap\FRS\Link\Retriever;
use Tuleap\FRS\UploadedLinksDao;
use Tuleap\FRS\UploadedLinksRetriever;
use Tuleap\REST\AuthenticatedResource;
use Tuleap\REST\Header;
use Tuleap\REST\ProjectStatusVerificator;
use UGroupManager;
use UserManager;

class PackageResource extends AuthenticatedResource
{
    public const MAX_LIMIT      = 50;
    public const DEFAULT_LIMIT  = 10;
    public const DEFAULT_OFFSET = 0;

    private $uploaded_link_retriever;
    private $package_factory;
    private $project_manager;
    private $retriever;

    /**
     * @var UserManager
     */
    private $user_manager;
    /**
     * @var FRSReleaseFactory
     */
    private $release_factory;
    /**
     * @var PackageRepresentationBuilder
     */
    private $package_representation_builder;
    /**
     * @var ReleasePermissionsForGroupsBuilder
     */
    private $release_permissions_for_groups_builder;

    public function __construct()
    {
        $this->package_factory         = FRSPackageFactory::instance();
        $this->release_factory         = FRSReleaseFactory::instance();
        $this->project_manager         = ProjectManager::instance();
        $this->retriever               = new Retriever(new Dao());
        $this->user_manager            = UserManager::instance();
        $this->uploaded_link_retriever = new UploadedLinksRetriever(new UploadedLinksDao(), $this->user_manager);
        $this->package_representation_builder = new PackageRepresentationBuilder(
            \PermissionsManager::instance(),
            new \UGroupManager(),
            FRSPermissionManager::build()
        );
        $this->release_permissions_for_groups_builder = new ReleasePermissionsForGroupsBuilder(
            FRSPermissionManager::build(),
            PermissionsManager::instance(),
            new UGroupManager()
        );
    }

    /**
     * Create a package
     *
     * Create a package in a given project. User must be file administrator to be able to create the package.
     *
     * The package will be active, and will be placed at the beginning of existing ones.
     *
     * @url POST
     * @status 201
     *
     * @param int    $project_id The id of the project where we should create the package {@from body}
     * @param string $label      Label of the package {@from body}
     *
     * @return \Tuleap\FRS\REST\v1\PackageRepresentation
     * @throws RestException 400 BadRequest Given project does not exist
     * @throws RestException 403 Forbidden User doesn't have permission to create a package
     * @throws RestException 409 Conflict Package with the same label already exists in this project
     * @throws RestException 500 Error Unable to create the package
     */
    protected function post($project_id, $label)
    {
        $project = $this->getProject($project_id);

        if (! $this->package_factory->userCanCreate($project->getID())) {
            throw new RestException(403, "User doesn't have permission to create a package");
        }

        if ($this->package_factory->isPackageNameExist($label, $project->getID())) {
            throw new RestException(409, "Package with the same label already exists in this project");
        }

        $package_array  = [
            'group_id'        => $project->getID(),
            'name'            => $label,
            'status_id'       => \FRSPackage::STATUS_ACTIVE,
            'rank'            => 'beginning',
            'approve_license' => 1
        ];
        $new_package_id = $this->package_factory->create($package_array);
        if (! $new_package_id) {
            throw new RestException(500, "Unable to create the package");
        }

        $this->sendOptionsHeaders();

        return $this->package_representation_builder->getPackageForUser($this->user_manager->getCurrentUser(), $this->getPackage($new_package_id), $project);
    }

    /**
     * @url OPTION
     */
    public function options()
    {
        $this->sendOptionsHeaders();
    }

    /**
     * Get FRS package
     *
     * @url GET {id}
     * @access hybrid
     *
     * @param int $id ID of the package
     *
     * @return \Tuleap\FRS\REST\v1\PackageRepresentation
     *
     * @throws RestException 403
     */
    public function getId($id)
    {
        $package = $this->getPackage($id);
        $project = $this->getPackageProject($package);

        ProjectStatusVerificator::build()->checkProjectStatusAllowsOnlySiteAdminToAccessIt(
            $this->user_manager->getCurrentUser(),
            $project
        );

        $this->sendOptionsHeadersForGetId();

        return $this->package_representation_builder->getPackageForUser($this->user_manager->getCurrentUser(), $package, $project);
    }

    /**
     * @url OPTION {id}
     */
    public function optionsId($id)
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
     *
     * @throws RestException 403
     */
    public function getReleases($id, $limit = self::DEFAULT_LIMIT, $offset = self::DEFAULT_OFFSET)
    {
        $package      = $this->getPackage($id);
        $current_user = $this->user_manager->getCurrentUser();

        ProjectStatusVerificator::build()->checkProjectStatusAllowsOnlySiteAdminToAccessIt(
            $current_user,
            $this->getPackageProject($package)
        );

        $paginated_releases = $this->release_factory->getPaginatedActiveFRSReleasesForUser(
            $package,
            $current_user,
            $limit,
            $offset
        );
        $total_size         = $paginated_releases->getTotalSize();

        $releases = [];
        foreach ($paginated_releases->getReleases() as $release) {
            $representation = new ReleaseRepresentation($release, $this->retriever, $current_user, $this->uploaded_link_retriever, $this->release_permissions_for_groups_builder);

            $releases[] = $representation;
        }

        $this->sendOptionsHeadersForReleases();
        $this->sendPaginationHeaders($limit, $offset, $total_size);

        return new ReleaseRepresentationPaginatedCollectionRepresentation($releases, $total_size);
    }

    /**
     * @url OPTION {id}/frs_releases
     */
    public function optionsReleases($id)
    {
        $this->sendOptionsHeadersForReleases();
    }

    /**
     * @return \FRSPackage
     */
    private function getPackage($id)
    {
        $package = $this->package_factory->getFRSPackageFromDb($id);

        if (! $package) {
            throw new RestException(404, "Package not found");
        }

        $user = $this->user_manager->getCurrentUser();

        if (! $this->package_factory->userCanRead($package->getGroupID(), $package->getPackageID(), $user->getId())) {
            throw new RestException(403, "Access to package denied");
        }

        if (! $package->isActive()) {
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

    /**
     * @param $project_id
     *
     * @return \Project
     * @throws RestException
     */
    private function getProject($project_id)
    {
        $project = $this->project_manager->getProject($project_id);

        ProjectStatusVerificator::build()->checkProjectStatusAllowsAllUsersToAccessIt($project);

        if ($project->isError() || ! $project->isActive()) {
            throw new RestException(400, "Given project does not exist");
        }

        return $project;
    }

    private function sendOptionsHeaders()
    {
        Header::allowOptionsPost();
    }

    private function getPackageProject(\FRSPackage $package)
    {
        return $this->project_manager->getProject(
            $package->getGroupID()
        );
    }
}
