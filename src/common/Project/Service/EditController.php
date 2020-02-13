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
use Tuleap\Project\Admin\Routing\ProjectAdministratorChecker;
use Tuleap\Request\DispatchableWithRequest;
use Tuleap\Request\ForbiddenException;
use Tuleap\Request\ProjectRetriever;

class EditController implements DispatchableWithRequest
{
    /**
     * @var ProjectRetriever
     */
    private $project_retriever;
    /**
     * @var ProjectAdministratorChecker
     */
    private $administrator_checker;
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
     * @var CSRFSynchronizerToken
     */
    private $csrf_token;

    public function __construct(
        ProjectRetriever $project_retriever,
        ProjectAdministratorChecker $administrator_checker,
        ServiceUpdator $service_updator,
        ServicePOSTDataBuilder $builder,
        ServiceManager $service_manager,
        CSRFSynchronizerToken $csrf_token
    ) {
        $this->service_updator       = $service_updator;
        $this->administrator_checker = $administrator_checker;
        $this->builder               = $builder;
        $this->service_manager       = $service_manager;
        $this->project_retriever     = $project_retriever;
        $this->csrf_token            = $csrf_token;
    }

    public static function buildSelf(): self
    {
        return new self(
            ProjectRetriever::buildSelf(),
            new ProjectAdministratorChecker(),
            new ServiceUpdator(new \ServiceDao(), ProjectManager::instance(), ServiceManager::instance()),
            new ServicePOSTDataBuilder(
                \EventManager::instance(),
                \ServiceManager::instance(),
                new ServiceLinkDataBuilder()
            ),
            ServiceManager::instance(),
            IndexController::getCSRFTokenSynchronizer()
        );
    }

    /**
     * @throws ForbiddenException
     * @throws \Tuleap\Request\NotFoundException
     */
    public function process(HTTPRequest $request, BaseLayout $layout, array $variables)
    {
        $project = $this->project_retriever->getProjectFromId($variables['id']);
        $this->administrator_checker->checkUserIsProjectAdministrator($request->getCurrentUser(), $project);

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
