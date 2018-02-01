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

class AddController
{
    /**
     * @var ServicePOSTDataBuilder
     */
    private $builder;
    /**
     * @var ServiceCreator
     */
    private $service_creator;

    public function __construct(ServiceCreator $service_creator, ServicePOSTDataBuilder $builder)
    {
        $this->builder         = $builder;
        $this->service_creator = $service_creator;
    }

    public function add(HTTPRequest $request)
    {
        $project      = $request->getProject();
        $service_data = $this->getServiceData($request);

        $this->checkShortname($project, $service_data);
        $this->checkScope($project, $service_data);

        try {
            $this->service_creator->createService($project, $service_data);
            $this->redirectToServiceAdministration($project);
        } catch (UnableToCreateServiceException $exception) {
            $this->redirectWithError(
                $project,
                $GLOBALS['Language']->getText('project_admin_servicebar', 'cant_create_s')
            );
        }
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

    /**
     * @param Project $project
     * @param ServicePOSTData $service_data
     */
    private function checkScope(Project $project, ServicePOSTData $service_data)
    {
        if ((int) $project->getID() !== 100 && $service_data->getScope() === "system") {
            $this->redirectWithError(
                $project,
                $GLOBALS['Language']->getText('project_admin_servicebar', 'cant_make_system_wide_s')
            );
        }
    }

    /**
     * @param Project $project
     * @param ServicePOSTData $service_data
     */
    private function checkShortname(Project $project, ServicePOSTData $service_data)
    {
        $short_name = $service_data->getShortName();
        if ($short_name) {
            // Check that the short_name is not already used
            $sql    = "SELECT * FROM service WHERE short_name='" . db_es($short_name) . "'";
            $result = db_query($sql);
            if (db_numrows($result) > 0) {
                $this->redirectWithError(
                    $project,
                    $GLOBALS['Language']->getText('project_admin_servicebar', 'short_name_exist')
                );
            }
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
}
