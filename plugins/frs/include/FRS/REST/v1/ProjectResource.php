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
use PFUser;
use Project;
use Luracast\Restler\RestException;
use ProjectManager;
use Tuleap\FRS\FRSPermissionDao;
use Tuleap\FRS\FRSPermissionFactory;
use Tuleap\FRS\FRSPermissionManager;
use Tuleap\REST\AuthenticatedResource;
use Tuleap\REST\Header;
use Tuleap\REST\ProjectAuthorization;
use URLVerification;
use UserManager;

class ProjectResource extends AuthenticatedResource
{
    private const MAX_LIMIT = 50;

    /**
     * @var FRSPackageFactory
     */
    private $package_factory;
    /**
     * @var UserManager
     */
    private $user_manager;
    /**
     * @var ProjectManager
     */
    private $project_manager;

    public function __construct()
    {
        $this->user_manager      = UserManager::instance();
        $this->project_manager   = ProjectManager::instance();
        $this->package_factory   = FRSPackageFactory::instance();
    }

    /**
     * Get FRS packages
     *
     * Get the list of packages in the project
     *
     * @url    GET {id}/frs_packages
     * @access hybrid
     *
     * @param int $id     Id of the project
     * @param int $limit  Number of elements displayed per page {@from path}
     * @param int $offset Position of the first element to display {@from path}
     *
     * @return array {@type \Tuleap\FRS\REST\v1\PackageMinimalRepresentation}
     * @pslam-var PackageMinimalRepresentation[]
     *
     * @throws RestException 403
     * @throws RestException 404
     * @throws RestException 406
     */
    public function getFRSPackages(int $id, int $limit = 10, int $offset = 0)
    {
        $this->checkAccess();
        $this->checkLimitValueIsAcceptable($limit);

        $project = $this->getProjectForUser($id);
        $paginated_packages = $this->getPackages($project, $this->user_manager->getCurrentUser(), $limit, $offset);

        $this->sendAllowHeadersForFRSPackages();
        $this->sendPaginationHeaders($limit, $offset, $paginated_packages->getTotalSize());

        return $paginated_packages->getPackageRepresentations();
    }

    /**
     * @throws RestException 403
     * @throws RestException 404
     *
     * @return Project
     */
    private function getProjectForUser(int $id)
    {
        $project = $this->project_manager->getProject($id);
        $user    = $this->user_manager->getCurrentUser();

        ProjectAuthorization::userCanAccessProject($user, $project, new URLVerification());

        return $project;
    }

    /**
     * @url OPTIONS {id}/frs_packages
     *
     * @param int $id Id of the project
     */
    public function optionsFRSPackages($id)
    {
        $this->sendAllowHeadersForFRSPackages();
    }

    private function getPackages(Project $project, PFUser $user, $limit, $offset)
    {
        if (! $project->usesFile()) {
            throw new RestException(404, 'File Release System service is not used by the project');
        }

        $packages = array();
        $paginated_packages = $this->package_factory->getPaginatedActivePackagesForUser(
            $project,
            $user,
            $limit,
            $offset
        );
        foreach ($paginated_packages->getPackages() as $package) {
            $representation = new PackageMinimalRepresentation();
            $representation->build($package);

            $packages[] = $representation;
        }

        return new PackageMinimalRepresentationPaginatedCollection($packages, $paginated_packages->getTotalSize());
    }

    /**
     * Get File Release System service
     *
     * Get metadata about the file release system service for a given project
     *
     * @url    GET {id}/frs_service
     * @access hybrid
     *
     * @param int $id     Id of the project
     *
     * @return ServiceRepresentation {@type \Tuleap\FRS\REST\v1\ServiceRepresentation}
     * @throws RestException 404
     * @throws RestException 403
     */
    public function getService(int $id): ServiceRepresentation
    {
        $builder = new ServiceRepresentationBuilder(
            FRSPermissionManager::build(),
            new FRSPermissionFactory(
                new FRSPermissionDao()
            ),
            new \UGroupManager()
        );
        return $builder->getServiceRepresentation($this->user_manager->getCurrentUser(), $this->getProjectForUser($id));
    }

    /**
     * @url OPTIONS {id}/frs_service
     *
     * @param int $id Id of the project
     */
    public function optionsService(int $id)
    {
        Header::allowOptionsGet();
    }

    private function sendAllowHeadersForFRSPackages()
    {
        Header::allowOptionsGet();
    }

    private function checkLimitValueIsAcceptable($limit)
    {
        if (! $this->limitValueIsAcceptable($limit)) {
            throw new RestException(406, 'Maximum value for limit exceeded');
        }
    }

    private function sendPaginationHeaders($limit, $offset, $size)
    {
        Header::sendPaginationHeaders($limit, $offset, $size, self::MAX_LIMIT);
    }

    private function limitValueIsAcceptable($limit)
    {
        return $limit <= self::MAX_LIMIT;
    }
}
