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
use RuntimeException;
use Service;
use ServiceDao;
use ServiceManager;
use Tuleap\Layout\BaseLayout;
use Tuleap\Project\Admin\Routing\ProjectAdministratorChecker;
use Tuleap\Request\DispatchableWithRequest;
use Tuleap\Request\ProjectRetriever;

class DeleteController implements DispatchableWithRequest
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
     * @var ServiceDao
     */
    private $dao;
    /**
     * @var CSRFSynchronizerToken
     */
    private $csrf_token;
    /**
     * @var ServiceManager
     */
    private $service_manager;

    public function __construct(
        ProjectRetriever $project_retriever,
        ProjectAdministratorChecker $administrator_checker,
        ServiceDao $dao,
        CSRFSynchronizerToken $csrf_token,
        ServiceManager $service_manager,
    ) {
        $this->project_retriever     = $project_retriever;
        $this->administrator_checker = $administrator_checker;
        $this->dao                   = $dao;
        $this->csrf_token            = $csrf_token;
        $this->service_manager       = $service_manager;
    }

    public static function buildSelf(): self
    {
        return new self(
            ProjectRetriever::buildSelf(),
            new ProjectAdministratorChecker(),
            new ServiceDao(),
            IndexController::getCSRFTokenSynchronizer(),
            ServiceManager::instance()
        );
    }

    public function process(HTTPRequest $request, BaseLayout $layout, array $variables)
    {
        $project = $this->project_retriever->getProjectFromId($variables['project_id']);
        $this->administrator_checker->checkUserIsProjectAdministrator($request->getCurrentUser(), $project);

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
        if ((int) $project->getID() !== Project::DEFAULT_TEMPLATE_PROJECT_ID) {
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
