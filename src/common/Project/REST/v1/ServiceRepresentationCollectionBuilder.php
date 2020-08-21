<?php
/**
 * Copyright (c) Enalean, 2019 - Present. All Rights Reserved.
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

declare(strict_types=1);

namespace Tuleap\Project\REST\v1;

use PFUser;
use Project;
use Service;
use ServiceManager;

class ServiceRepresentationCollectionBuilder
{
    /**
     * @var ServiceManager
     */
    private $service_manager;

    public function __construct(ServiceManager $service_manager)
    {
        $this->service_manager = $service_manager;
    }

    /**
     * @return ServiceRepresentation[]
     */
    public function getServiceRepresentationCollectionForProject(Project $project, PFUser $user): array
    {
        $collection = [];
        $allowed_services = $this->service_manager->getListOfAllowedServicesForProject($project);
        foreach ($allowed_services as $service) {
            if (! $this->isServiceReadable($service, $user)) {
                continue;
            }

            $collection[] = ServiceRepresentation::build($service);
        }

        return $collection;
    }

    private function isServiceReadable(Service $service, PFUser $user): bool
    {
        if ((int) $service->getId() === 100) {
            return false;
        }

        if ($user->isSuperUser()) {
            return true;
        }

        return (bool) $service->isActive();
    }
}
