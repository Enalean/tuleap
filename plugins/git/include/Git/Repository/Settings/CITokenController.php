<?php
/**
 * Copyright (c) Enalean, 2017. All Rights Reserved.
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
use Tuleap\Git\CIToken\Manager;
use Tuleap\Git\GitViews\RepoManagement\Pane\GitViewsRepoManagementPaneCIToken;
use Tuleap\Git\Repository\RepositoryFromRequestRetriever;

class CITokenController extends SettingsController
{
    /**
     * @var Manager
     */
    private $manager;

    public function __construct(RepositoryFromRequestRetriever $repository_retriever, Manager $manager)
    {
        parent::__construct($repository_retriever);
        $this->manager = $manager;
    }

    public function generateToken(HTTPRequest $request)
    {
        $this->checkCSRF($request);

        $repository = $this->getRepositoryUserCanAdministrate($request);
        $this->manager->generateNewTokenForRepository($repository);

        $this->redirect($repository);
    }

    private function checkCSRF(HTTPRequest $request)
    {
        $project_id = $request->getProject()->getID();
        $token = new CSRFSynchronizerToken('plugins/git/?group_id=' . $project_id . '&pane=citoken');
        $token->check();
    }

    private function redirect(GitRepository $repository)
    {
        $redirect_url = GIT_BASE_URL . '/?' . http_build_query([
                'action' => 'repo_management',
                'group_id' => $repository->getProjectId(),
                'repo_id' => $repository->getId(),
                'pane' => GitViewsRepoManagementPaneCIToken::ID
            ]);

        $GLOBALS['Response']->redirect($redirect_url);
    }
}
