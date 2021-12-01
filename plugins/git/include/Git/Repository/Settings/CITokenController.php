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

namespace Tuleap\Git\Repository\Settings;

use CSRFSynchronizerToken;
use GitRepository;
use HTTPRequest;
use Tuleap\Git\CIBuilds\BuildStatusChangePermissionManager;
use Tuleap\Git\CIBuilds\CITokenManager;
use Tuleap\Git\GitViews\RepoManagement\Pane\CIBuilds;
use Tuleap\Git\Repository\RepositoryFromRequestRetriever;

class CITokenController extends SettingsController
{
    /**
     * @var CITokenManager
     */
    private $manager;
    /**
     * @var BuildStatusChangePermissionManager
     */
    private $build_status_change_manager;

    public function __construct(
        RepositoryFromRequestRetriever $repository_retriever,
        CITokenManager $manager,
        BuildStatusChangePermissionManager $build_status_change_manager,
    ) {
        parent::__construct($repository_retriever);
        $this->manager                     = $manager;
        $this->build_status_change_manager = $build_status_change_manager;
    }

    public function generateToken(HTTPRequest $request)
    {
        $this->checkCSRF($request);

        $repository = $this->getRepositoryUserCanAdministrate($request);
        $this->manager->generateNewTokenForRepository($repository);

        $this->redirect($repository);
    }

    public function setBuildStatusChangePermission(HTTPRequest $request): void
    {
        $this->checkCSRF($request);

        $repository  = $this->getRepositoryUserCanAdministrate($request);
        $permissions = $request->get('set-build-status-permissions') ?: [];

        $this->build_status_change_manager->updateBuildStatusChangePermissions(
            $repository,
            $permissions
        );

        $this->redirect($repository);
    }

    private function checkCSRF(HTTPRequest $request)
    {
        $project_id = $request->getProject()->getID();
        $pane_url   = GIT_BASE_URL . '/?' . http_build_query([
            'group_id' => $project_id,
            'pane' => CIBuilds::ID,
        ]);
        $token      = new CSRFSynchronizerToken($pane_url);
        $token->check();
    }

    private function redirect(GitRepository $repository)
    {
        $redirect_url = GIT_BASE_URL . '/?' . http_build_query([
            'action' => 'repo_management',
            'group_id' => $repository->getProjectId(),
            'repo_id' => $repository->getId(),
            'pane' => CIBuilds::ID,
        ]);

        $GLOBALS['Response']->redirect($redirect_url);
    }
}
