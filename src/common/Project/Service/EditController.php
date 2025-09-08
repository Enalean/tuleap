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
use Psr\EventDispatcher\EventDispatcherInterface;
use ServiceManager;
use Tuleap\Layout\BaseLayout;
use Tuleap\Project\Admin\Routing\ProjectAdministratorChecker;
use Tuleap\Project\ProjectByIDFactory;
use Tuleap\Request\DispatchableWithRequest;
use Tuleap\Request\ForbiddenException;
use Tuleap\Request\NotFoundException;

class EditController implements DispatchableWithRequest
{
    public function __construct(
        private ProjectByIDFactory $project_retriever,
        private ProjectAdministratorChecker $administrator_checker,
        private ServiceUpdator $service_updator,
        private ServicePOSTDataBuilder $builder,
        private ServiceManager $service_manager,
        private CSRFSynchronizerToken $csrf_token,
        private EventDispatcherInterface $dispatcher,
    ) {
    }

    public static function buildSelf(): self
    {
        return new self(
            ProjectManager::instance(),
            new ProjectAdministratorChecker(),
            new ServiceUpdator(new ServiceDao(), ProjectManager::instance(), ServiceManager::instance()),
            new ServicePOSTDataBuilder(
                new ServiceLinkDataBuilder()
            ),
            ServiceManager::instance(),
            IndexController::getCSRFTokenSynchronizer(),
            \EventManager::instance(),
        );
    }

    /**
     * @throws ForbiddenException
     * @throws \Tuleap\Request\NotFoundException
     */
    #[\Override]
    public function process(HTTPRequest $request, BaseLayout $layout, array $variables): void
    {
        try {
            $project = $this->project_retriever->getValidProjectById((int) $variables['project_id']);
        } catch (\Project_NotFoundException $exception) {
            throw new NotFoundException(gettext('Project does not exist'));
        }

        try {
            $user = $request->getCurrentUser();
            $this->administrator_checker->checkUserIsProjectAdministrator($user, $project);

            $this->csrf_token->check(IndexController::getUrl($project));

            $all_services    = $this->service_manager->getListOfAllowedServicesForProject($project);
            $current_service = $all_services[(int) $request->get('service_id')] ?? null;
            $service_data    = $this->builder->buildFromRequest($request, $project, $current_service, $layout);

            $this->checkId($project, $layout, $service_data);
            if ($service_data->getId() === \Service::FAKE_ID_FOR_CREATION) {
                $add_missing_service = $this->dispatcher->dispatch(new AddMissingService($project, []));
                foreach ($add_missing_service->getAllowedServices() as $missing_service) {
                    if ($missing_service->getShortName() === $service_data->getShortName()) {
                        $this->service_updator->addSystemService($project, $missing_service, $service_data);
                        break;
                    }
                }
            } else {
                $this->checkServiceIsAllowedForProject($project, $layout, $service_data);
                $this->checkServiceCanBeUpdated($project, $service_data, $user);
                $this->service_updator->updateService($project, $service_data, $user);
            }
            $layout->addFeedback(
                Feedback::INFO,
                _('Service updated successfully')
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
    private function checkServiceCanBeUpdated(Project $project, ServicePOSTData $service_data, \PFUser $user): void
    {
        if (! $service_data->isSystemService()) {
            return;
        }

        $this->service_manager->checkServiceCanBeUpdated(
            $project,
            $service_data->getShortName(),
            $service_data->isUsed(),
            $user
        );
    }
}
