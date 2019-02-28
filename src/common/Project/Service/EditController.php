<?php
/**
 * Copyright (c) Enalean, 2018. All Rights Reserved.
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

use Feedback;
use HTTPRequest;
use Project;
use ServiceManager;

class EditController
{
    /**
     * @var ServiceUpdator
     */
    private $service_updator;
    /**
     * @var ServicePOSTDataBuilder
     */
    private $builder;
    /**
     * @var ServiceManager
     */
    private $service_manager;

    public function __construct(
        ServiceUpdator $service_updator,
        ServicePOSTDataBuilder $builder,
        ServiceManager $service_manager
    ) {
        $this->service_updator = $service_updator;
        $this->builder         = $builder;
        $this->service_manager = $service_manager;
    }

    public function edit(HTTPRequest $request)
    {
        $user         = $request->getCurrentUser();
        $project      = $request->getProject();
        $service_data = $this->getServiceData($request);

        $this->checkId($project, $service_data);
        $this->checkServiceIsAllowedForProject($project, $service_data);
        $this->checkServiceCanBeUpdated($project, $service_data);

        $this->service_updator->updateService($project, $service_data, $user);
        $GLOBALS['Response']->addFeedback(
            Feedback::INFO,
            $GLOBALS['Language']->getText('project_admin_servicebar', 's_update_success')
        );
        $this->redirectToServiceAdministration($project);
    }

    /**
     * @param HTTPRequest $request
     * @return ServicePOSTData
     */
    private function getServiceData(HTTPRequest $request)
    {
        try {
            return $this->builder->buildFromRequest($request);
        } catch (InvalidServicePOSTDataException $exception) {
            $this->redirectWithError(
                $request->getProject(),
                $exception->getMessage()
            );
        }
    }

    private function checkId(Project $project, ServicePOSTData $service_data)
    {
        if (! $service_data->getId()) {
            $this->redirectWithError(
                $project,
                $GLOBALS['Language']->getText('project_admin_servicebar', 's_id_missed')
            );
        }
    }

    /**
     * @param Project $project
     * @param string $message
     */
    private function redirectWithError(Project $project, $message)
    {
        $GLOBALS['Response']->addFeedback(Feedback::ERROR, $message);
        $this->redirectToServiceAdministration($project);
    }

    /**
     * @param Project $project
     */
    private function redirectToServiceAdministration(Project $project)
    {
        $GLOBALS['Response']->redirect('/project/admin/servicebar.php?group_id=' . $project->getID());
    }

    /**
     * @param Project $project
     * @param ServicePOSTData $service_data
     */
    private function checkServiceIsAllowedForProject(Project $project, ServicePOSTData $service_data)
    {
        if (! $this->service_manager->isServiceAllowedForProject($project, $service_data->getId())) {
            $this->redirectWithError(
                $project,
                $GLOBALS['Language']->getText('project_admin_servicebar', 'not_allowed')
            );
        }
    }

    /**
     * @param Project $project
     * @param ServicePOSTData $service_data
     */
    private function checkServiceCanBeUpdated(Project $project, ServicePOSTData $service_data)
    {
        if ($service_data->isSystemService()) {
            $updatable = $this->service_manager->checkServiceCanBeUpdated(
                $project,
                $service_data->getShortName(),
                $service_data->isUsed()
            );

            if (! $updatable) {
                $this->redirectToServiceAdministration($project);
            }
        }
    }
}
