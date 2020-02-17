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
use Tuleap\Project\Admin\Routing\ProjectAdministratorChecker;
use Tuleap\Request\DispatchableWithRequest;
use Tuleap\Request\ProjectRetriever;

class AddController implements DispatchableWithRequest
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
     * @var ServicePOSTDataBuilder
     */
    private $builder;
    /**
     * @var ServiceCreator
     */
    private $service_creator;
    /**
     * @var \CSRFSynchronizerToken
     */
    private $csrf_token;

    public function __construct(
        ProjectRetriever $project_retriever,
        ProjectAdministratorChecker $project_administrator_checker,
        ServiceCreator $service_creator,
        ServicePOSTDataBuilder $builder,
        \CSRFSynchronizerToken $csrf_token
    ) {
        $this->project_retriever     = $project_retriever;
        $this->administrator_checker = $project_administrator_checker;
        $this->builder               = $builder;
        $this->service_creator       = $service_creator;
        $this->csrf_token            = $csrf_token;
    }

    public static function buildSelf(): self
    {
        return new self(
            ProjectRetriever::buildSelf(),
            new ProjectAdministratorChecker(),
            new ServiceCreator(new \ServiceDao(), ProjectManager::instance()),
            new ServicePOSTDataBuilder(
                \EventManager::instance(),
                \ServiceManager::instance(),
                new ServiceLinkDataBuilder()
            ),
            IndexController::getCSRFTokenSynchronizer()
        );
    }

    /**
     * @throws \Tuleap\Request\ForbiddenException
     * @throws \Tuleap\Request\NotFoundException
     */
    public function process(HTTPRequest $request, BaseLayout $layout, array $variables)
    {
        $project = $this->project_retriever->getProjectFromId($variables['id']);
        $this->administrator_checker->checkUserIsProjectAdministrator($request->getCurrentUser(), $project);

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
