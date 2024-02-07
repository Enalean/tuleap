<?php
/**
 * Copyright (c) Enalean, 2019 - Present. All Rights Reserved.
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
 * along with Tuleap; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

declare(strict_types=1);

namespace Tuleap\Project\REST\v1;

use Luracast\Restler\RestException;
use PFUser;
use ProjectManager;
use Service;
use ServiceDao;
use ServiceNotAllowedForProjectException;
use Tuleap\Project\REST\v1\Service\ServicePUTRepresentation;
use Tuleap\Project\REST\v1\Service\ServiceUpdateChecker;
use Tuleap\Project\Service\ServiceCannotBeUpdatedException;
use Tuleap\Project\Service\ServiceLinkDataBuilder;
use Tuleap\Project\Service\ServiceNotFoundException;
use Tuleap\Project\Service\ServicePOSTData;
use Tuleap\Project\Service\ServicePOSTDataBuilder;
use Tuleap\Project\Service\ServiceUpdator;
use Tuleap\REST\AuthenticatedResource;
use Tuleap\REST\Header;
use Tuleap\REST\I18NRestException;
use Tuleap\REST\ProjectAuthorization;
use URLVerification;
use User_LoginException;
use UserManager;

/**
 * Wrapper for project related REST methods
 */
class ServiceResource extends AuthenticatedResource
{
    /**
     * @var \ServiceManager
     */
    private $service_manager;
    /**
     * @var ServiceUpdator
     */
    private $service_updator;

    public function __construct()
    {
        $this->service_manager = \ServiceManager::instance();
        $this->service_updator = new ServiceUpdator(
            new ServiceDao(),
            ProjectManager::instance(),
            $this->service_manager
        );
    }

    /**
     * @url    OPTIONS {id}
     *
     * @param int $id Id of the project
     */
    public function optionsId(int $id): void
    {
        $this->sendAllowHeaders();
    }

    /**
     * Update service
     *
     * Update the service of a project.
     * <p>
     * For now, the service can only be enabled or disabled. Just set is_enabled to true or false according to your need.
     * </p>
     *
     * @url    PUT {id}
     * @access hybrid
     *
     * @param int                   $id   The id of the service
     * @param ServicePUTRepresentation $body The service data
     *
     * @throws RestException 400
     * @throws RestException 401
     * @throws RestException 403
     * @throws RestException 404
     */
    public function putId(int $id, ServicePUTRepresentation $body): void
    {
        $this->checkAccess();
        $user    = $this->getUser();
        $service = $this->getService($id, $user);
        $this->sendAllowHeaders();
        try {
            (new ServiceUpdateChecker($this->service_manager))->checkServiceCanBeUpdated($body, $service, $user);
            $this->service_updator->updateService(
                $service->getProject(),
                $this->getServicePOSTDataFromBody($service, $body),
                $user
            );
        } catch (ServiceCannotBeUpdatedException $exception) {
            throw new I18NRestException(400, $exception->getMessage());
        }
    }

    private function sendAllowHeaders(): void
    {
        Header::allowOptionsPut();
    }

    /**
     * @throws RestException 403
     * @throws RestException 404
     */
    private function getService(int $id, PFUser $user): Service
    {
        try {
            $service = $this->service_manager->getService($id);
            $project = $service->getProject();

            ProjectAuthorization::userCanAccessProject($user, $project, new URLVerification());

            if (! $user->isAdmin($project->getID())) {
                throw new RestException(403);
            }

            return $service;
        } catch (ServiceNotFoundException $exception) {
            throw new RestException(404);
        } catch (ServiceNotAllowedForProjectException $exception) {
            throw new RestException(400, _('Service is not allowed in project.'));
        }
    }

    /**
     * @throws I18NRestException
     */
    private function getUser(): PFUser
    {
        try {
            return UserManager::instance()->getCurrentUser();
        } catch (\Rest_Exception_InvalidTokenException | User_LoginException $exception) {
            throw new I18NRestException(401, $exception->getMessage());
        }
    }

    private function getServicePOSTDataFromBody(Service $service, ServicePUTRepresentation $body): ServicePOSTData
    {
        $builder = new ServicePOSTDataBuilder(
            new ServiceLinkDataBuilder()
        );

        return $builder->buildFromREST($service, $body->is_enabled);
    }
}
