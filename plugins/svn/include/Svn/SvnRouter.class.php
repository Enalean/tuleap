<?php
/**
 * Copyright (c) Enalean, 2015-2016. All Rights Reserved.
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

namespace Tuleap\Svn;

use HTTPRequest;
use \Tuleap\Svn\Explorer\ExplorerController;
use \Tuleap\Svn\Explorer\RepositoryDisplayController;
use Tuleap\Svn\Repository\RepositoryManager;
use Tuleap\Svn\Repository\RuleName;
use \Tuleap\Svn\Repository\CannotFindRepositoryException;
use Tuleap\Svn\Admin\AdminController;
use Tuleap\Svn\AuthFile\AccessFileHistoryManager;
use ProjectManager;
use Project;
use Rule_ProjectName;

class SvnRouter {

    const DEFAULT_ACTION = 'index';

    private $repository_manager;
    private $project_manager;

    public function __construct(
            RepositoryManager $repository_manager,
            ProjectManager $project_manager
        ) {
        $this->repository_manager  = $repository_manager;
        $this->project_manager     = $project_manager;
    }

    /**
     * Routes the request to the correct controller
     * @param HTTPRequest $request
     * @return void
     */
    public function route(HTTPRequest $request) {

        try {
            $this->useAViewVcRoadIfRootValid($request);
        } catch (CannotFindRepositoryException $e) {
            $GLOBALS['Response']->addFeedback('info', $request->get('root'). " " .$GLOBALS['Language']->getText('plugin_svn','find_error'));
            $GLOBALS['Response']->redirect('/');
        }

        if (! $request->get('action')) {
            $this->useDefaultRoute($request);
            return;
        }

        $action = $request->get('action');
        switch ($action) {
            case "createRepo":
                $controller = new ExplorerController($this->repository_manager);
                $controller->$action($this->getService($request), $request);
                break;
            case "displayRepo":
                $controller = new RepositoryDisplayController($this->repository_manager, $this->project_manager);
                $controller->$action($this->getService($request), $request);
                break;
            default:
                $this->useDefaultRoute($request);
                break;
        }
    }

    private function useAViewVcRoadIfRootValid(HTTPRequest $request) {
        if ($request->get('roottype')) {
            if (preg_match('/^('.Rule_ProjectName::PATTERN_PROJECT_NAME.')\/('.RuleName::PATTERN_REPOSITORY_NAME.')$/', $request->get('root'), $matches)) {
                $svn_dir = $matches;
            } else {
                throw new CannotFindRepositoryException($GLOBALS['Language']->getText('plugin_svn','find_error'));
            }

            $project = $this->project_manager->getProjectByUnixName($svn_dir[1]);
            if (! $project instanceof Project) {
                throw new CannotFindRepositoryException($GLOBALS['Language']->getText('plugin_svn','find_error'));
            }

            $repository = $this->repository_manager->getRepositoryByName($project, $svn_dir[2]);
            $request->set("group_id", $repository->getProject()->getId());
            $request->set("repo_id", $repository->getId());

            $this->useViewVcRoute($request);
            return;
        }
    }

    /**
     * @param HTTPRequest $request
     */
    private function useViewVcRoute(HTTPRequest $request) {
        $controller = new RepositoryDisplayController($this->repository_manager, $this->project_manager);
        $controller->displayRepo($this->getService($request), $request);
    }

    /**
     * @param HTTPRequest $request
     */
    private function useDefaultRoute(HTTPRequest $request) {
        $action = self::DEFAULT_ACTION;
        $controller = new ExplorerController($this->repository_manager);
        $controller->$action( $this->getService($request), $request );
    }

    /**
     * Retrieves the SVN Service instance matching the request group id.
     *
     * @param HTTPRequest $request
     *
     * @return ServiceSvn
     */
    private function getService(HTTPRequest $request) {
        return $request->getProject()->getService('plugin_svn');
    }
}
