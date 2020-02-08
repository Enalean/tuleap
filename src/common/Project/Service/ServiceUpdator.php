<?php
/**
 * Copyright (c) Enalean, 2018-Present. All Rights Reserved.
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

namespace Tuleap\Project\Service;

use PFUser;
use Project;
use ProjectManager;
use ServiceDao;
use ServiceManager;

class ServiceUpdator
{
    /**
     * @var ServiceDao
     */
    private $dao;
    /**
     * @var ProjectManager
     */
    private $project_manager;
    /**
     * @var ServiceManager
     */
    private $service_manager;

    public function __construct(ServiceDao $dao, ProjectManager $project_manager, ServiceManager $service_manager)
    {
        $this->dao             = $dao;
        $this->project_manager = $project_manager;
        $this->service_manager = $service_manager;
    }

    public function updateService(Project $project, ServicePOSTData $service_data, PFUser $user): void
    {
        $this->dao->saveBasicInformation(
            $service_data->getId(),
            $service_data->getLabel(),
            $service_data->getIconName(),
            $service_data->getDescription(),
            $service_data->getLink(),
            $service_data->getRank(),
            $service_data->isInIframe(),
            $service_data->isInNewTab()
        );

        if ($user->isSuperUser()) {
            $this->dao->saveIsActiveAndScope($service_data->getId(), $service_data->isActive(), $service_data->getScope());
        }

        $this->project_manager->clear($project->getID());

        if ($service_data->isSystemService()) {
            $this->service_manager->toggleServiceUsage($project, $service_data->getShortName(), $service_data->isUsed());
        } else {
            $this->dao->updateServiceUsageByServiceID($project->getID(), $service_data->getId(), $service_data->isUsed());
        }
    }
}
