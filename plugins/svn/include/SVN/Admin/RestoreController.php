<?php
/**
 * Copyright (c) Enalean, 2016-2018. All Rights Reserved.
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

namespace Tuleap\SVN\Admin;

use HTTPRequest;
use SystemEventManager;
use TemplateRendererFactory;
use Tuleap\SVN\Repository\RepositoryManager;

class RestoreController
{
    private $repository_manager;

    public function __construct(RepositoryManager $repository_manager)
    {
        $this->repository_manager = $repository_manager;
    }

    public function restoreRepository(HTTPRequest $request)
    {
        $project       = $request->getProject();
        $project_id    = $project->getID();
        $repository_id = $request->get('repo_id');

        if (! $project_id || ! $repository_id) {
            $GLOBALS['Response']->addFeedback('error', 'actions_params_error');
            return false;
        }

        $url = '/admin/show_pending_documents.php?' . http_build_query(['group_id' => $project_id]);
        (new \CSRFSynchronizerToken($url))->check();

        $repository = $this->repository_manager->getByIdAndProject($repository_id, $project);
        if ($repository !== null) {
            $this->repository_manager->queueRepositoryRestore($repository, SystemEventManager::instance());
        }

        $GLOBALS['Response']->redirect($url);
    }

    public function displayRestorableRepositories(\CSRFSynchronizerToken $csrf_token, array $repositories, $project_id)
    {
        $presenter = new RestorePresenter($csrf_token, $repositories, $project_id);
        $renderer  = TemplateRendererFactory::build()->getRenderer(SVN_TEMPLATE_DIR);
        return $renderer->renderToString('repository_restore', $presenter);
    }
}
