<?php
/**
 * Copyright (c) Enalean, 2018 - Present. All Rights Reserved.
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
use ProjectManager;
use Tuleap\Layout\BaseLayout;
use Tuleap\Request\DispatchableWithProject;
use Tuleap\Request\DispatchableWithRequest;
use Tuleap\Request\ForbiddenException;
use Tuleap\Request\NotFoundException;

class AddController implements DispatchableWithRequest, DispatchableWithProject
{
    /**
     * @var ServicePOSTDataBuilder
     */
    private $builder;
    /**
     * @var ServiceCreator
     */
    private $service_creator;
    /**
     * @var ProjectManager
     */
    private $project_manager;
    /**
     * @var \CSRFSynchronizerToken
     */
    private $csrf_token;

    public function __construct(ServiceCreator $service_creator, ServicePOSTDataBuilder $builder, ProjectManager $project_manager, \CSRFSynchronizerToken $csrf_token)
    {
        $this->builder         = $builder;
        $this->service_creator = $service_creator;
        $this->project_manager = $project_manager;
        $this->csrf_token      = $csrf_token;
    }

    /**
     * @throws NotFoundException
     */
    public function getProject(array $variables): Project
    {
        $project = $this->project_manager->getProject($variables['id']);
        if (! $project || $project->isError()) {
            throw new NotFoundException();
        }
        return $project;
    }

    public function process(HTTPRequest $request, BaseLayout $layout, array $variables)
    {
        $project = $this->getProject($variables);

        if (! $request->getCurrentUser()->isAdmin($project->getID())) {
            throw new ForbiddenException();
        }

        $this->csrf_token->check(IndexController::getUrl($project));

        try {
            $service_data = $this->builder->buildFromRequest($request, $project, $layout);

            $this->checkShortname($project, $layout, $service_data);
            $this->checkScope($project, $layout, $service_data);

            $this->service_creator->createService($project, $service_data);
            $this->redirectToServiceAdministration($project, $layout);
        } catch (UnableToCreateServiceException $exception) {
            $this->redirectWithError(
                $project,
                $layout,
                $GLOBALS['Language']->getText('project_admin_servicebar', 'cant_create_s')
            );
        } catch (InvalidServicePOSTDataException $exception) {
            $this->redirectWithError(
                $project,
                $layout,
                $exception->getMessage()
            );
        }
    }

    private function checkScope(Project $project, BaseLayout $response, ServicePOSTData $service_data): void
    {
        if ((int) $project->getID() !== 100 && $service_data->getScope() === "system") {
            $this->redirectWithError(
                $project,
                $response,
                $GLOBALS['Language']->getText('project_admin_servicebar', 'cant_make_system_wide_s')
            );
        }
    }

    private function checkShortname(Project $project, BaseLayout $response, ServicePOSTData $service_data): void
    {
        $short_name = $service_data->getShortName();
        if ($short_name) {
            // Check that the short_name is not already used
            $sql    = "SELECT * FROM service WHERE short_name='" . db_es($short_name) . "'";
            $result = db_query($sql);
            if (db_numrows($result) > 0) {
                $this->redirectWithError(
                    $project,
                    $response,
                    $GLOBALS['Language']->getText('project_admin_servicebar', 'short_name_exist')
                );
            }
        }
    }

    private function redirectWithError(Project $project, BaseLayout $response, string $message): void
    {
        $response->addFeedback(Feedback::ERROR, $message);
        $this->redirectToServiceAdministration($project, $response);
    }

    private function redirectToServiceAdministration(Project $project, BaseLayout $response): void
    {
        $response->redirect(IndexController::getUrl($project));
    }
}
