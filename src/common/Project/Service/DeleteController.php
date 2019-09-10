<?php
/**
 * Copyright (c) Enalean, 2017 - Present. All Rights Reserved.
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
use RuntimeException;
use Service;
use ServiceDao;
use ServiceManager;
use Tuleap\Layout\BaseLayout;
use Tuleap\Request\DispatchableWithProject;
use Tuleap\Request\DispatchableWithRequest;
use Tuleap\Request\ForbiddenException;
use Tuleap\Request\NotFoundException;

class DeleteController implements DispatchableWithRequest, DispatchableWithProject
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
     * @var CSRFSynchronizerToken
     */
    private $csrf_token;
    /**
     * @var ServiceManager
     */
    private $service_manager;

    public function __construct(ServiceDao $dao, ProjectManager $project_manager, CSRFSynchronizerToken $csrf_token, ServiceManager $service_manager)
    {
        $this->dao = $dao;
        $this->project_manager = $project_manager;
        $this->csrf_token = $csrf_token;
        $this->service_manager = $service_manager;
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
            $service_id = (int) $request->getValidated('service_id', 'uint', 0);
            if (! $service_id) {
                throw new RuntimeException($GLOBALS['Language']->getText('project_admin_servicebar', 's_id_not_given'));
            }

            $service = $this->service_manager->getService($service_id);

            if ($service->getScope() === Service::SCOPE_SYSTEM) {
                throw new RuntimeException(_('This service is a system service, it cannot be deleted.'));
            }

            if (! $this->dao->delete($project->getID(), $service->getId())) {
                throw new RuntimeException($GLOBALS['Language']->getText('project_admin_editgroupinfo', 'upd_fail'));
            }
            $layout->addFeedback(Feedback::INFO, $GLOBALS['Language']->getText('project_admin_servicebar', 's_del'));

            $this->deleteFromAllProjects($request, $layout, $project, $service);
        } catch (ServiceNotFoundException $exception) {
            $layout->addFeedback(Feedback::ERROR, _('Service not found in database'));
        } catch (RuntimeException $exception) {
            $layout->addFeedback(Feedback::ERROR, $exception->getMessage());
        }

        $layout->redirect(IndexController::getUrl($project));
    }

    private function deleteFromAllProjects(HTTPRequest $request, BaseLayout $response, Project $project, Service $service): void
    {
        if ((int) $project->getID() !== Project::ADMIN_PROJECT_ID) {
            return;
        }

        if (! $service->getShortName()) {
            throw new RuntimeException($GLOBALS['Language']->getText('project_admin_servicebar', 'cant_delete_s_from_p'));
        }

        if (! $this->dao->deleteFromAllProjects($service->getShortName())) {
            throw new RuntimeException($GLOBALS['Language']->getText('project_admin_servicebar', 'del_fail'));
        }

        $response->addFeedback(
            Feedback::INFO,
            $GLOBALS['Language']->getText('project_admin_servicebar', 's_del_from_p')
        );
    }
}
