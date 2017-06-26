<?php
/**
 * Copyright (c) Enalean, 2016. All Rights Reserved.
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


namespace Tuleap\Svn\Admin;

use HTTPRequest;
use SystemEventManager;
use TemplateRendererFactory;
use Tuleap\Svn\Repository\RepositoryManager;

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

        $repository = $this->repository_manager->getByIdAndProject($repository_id, $project);
        if ($repository !== null) {
            $this->repository_manager->queueRepositoryRestore($repository, SystemEventManager::instance());
        }

        $GLOBALS['Response']->redirect('/admin/show_pending_documents.php?'. http_build_query(
            array('group_id' => $project_id)
        ));
    }

    public function displayRestorableRepositories(array $repositories, $project_id)
    {
        $presenter = new RestorePresenter($repositories, $project_id);
        $renderer  = TemplateRendererFactory::build()->getRenderer(SVN_TEMPLATE_DIR);
        return $renderer->renderToString('repository_restore', $presenter);
    }
}