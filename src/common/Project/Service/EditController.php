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

use CSRFSynchronizerToken;
use Feedback;
use HTTPRequest;
use Project;
use ProjectManager;
use ServiceManager;
use Tuleap\Layout\BaseLayout;
use Tuleap\Request\DispatchableWithProject;
use Tuleap\Request\DispatchableWithRequest;
use Tuleap\Request\ForbiddenException;
use Tuleap\Request\NotFoundException;

class EditController implements DispatchableWithRequest, DispatchableWithProject
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
    /**
     * @var ProjectManager
     */
    private $project_manager;
    /**
     * @var CSRFSynchronizerToken
     */
    private $csrf_token;

    public function __construct(
        ServiceUpdator $service_updator,
        ServicePOSTDataBuilder $builder,
        ServiceManager $service_manager,
        ProjectManager $project_manager,
        CSRFSynchronizerToken $csrf_token
    ) {
        $this->service_updator = $service_updator;
        $this->builder         = $builder;
        $this->service_manager = $service_manager;
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

            $this->checkId($project, $layout, $service_data);
            $this->checkServiceIsAllowedForProject($project, $layout, $service_data);
            $this->checkServiceCanBeUpdated($project, $service_data);

            $this->service_updator->updateService($project, $service_data, $request->getCurrentUser());
            $layout->addFeedback(
                Feedback::INFO,
                $GLOBALS['Language']->getText('project_admin_servicebar', 's_update_success')
            );
            $this->redirectToServiceAdministration($project, $layout);
        } catch (InvalidServicePOSTDataException $exception) {
            $this->redirectWithError(
                $project,
                $layout,
                $exception->getMessage()
            );
        } catch (ServiceCannotBeUpdatedException $exception) {
            $layout->addFeedback(
                Feedback::WARN,
                $exception->getMessage()
            );
            $this->redirectToServiceAdministration($project, $layout);
        }
    }

    private function checkId(Project $project, BaseLayout $response, ServicePOSTData $service_data): void
    {
        if (! $service_data->getId()) {
            $this->redirectWithError(
                $project,
                $response,
                $GLOBALS['Language']->getText('project_admin_servicebar', 's_id_missed')
            );
        }
    }

    private function redirectWithError(Project $project, BaseLayout $response, $message): void
    {
        $response->addFeedback(Feedback::ERROR, $message);
        $this->redirectToServiceAdministration($project, $response);
    }

    private function redirectToServiceAdministration(Project $project, BaseLayout $response): void
    {
        $response->redirect(IndexController::getUrl($project));
    }

    private function checkServiceIsAllowedForProject(Project $project, BaseLayout $response, ServicePOSTData $service_data): void
    {
        if (! $this->service_manager->isServiceAllowedForProject($project, $service_data->getId())) {
            $this->redirectWithError(
                $project,
                $response,
                $GLOBALS['Language']->getText('project_admin_servicebar', 'not_allowed')
            );
        }
    }

    /**
     * @throws ServiceCannotBeUpdatedException
     */
    private function checkServiceCanBeUpdated(Project $project, ServicePOSTData $service_data): void
    {
        if (! $service_data->isSystemService()) {
            return;
        }

        $this->service_manager->checkServiceCanBeUpdated(
            $project,
            $service_data->getShortName(),
            $service_data->isUsed()
        );
    }
}
