<?php

/**
 * Copyright (c) Enalean, 2016. All rights reserved
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/
 */

namespace Tuleap\Svn\Explorer;

use Tuleap\Svn\ServiceSvn;
use Tuleap\Svn\SvnPermissionManager;
use HTTPRequest;
use Tuleap\Svn\Explorer\RepositoryDisplayPresenter;
use \Tuleap\Svn\Repository\RepositoryManager;
use \Tuleap\Svn\Repository\RepositoryNotFoundException;
use Tuleap\Svn\ViewVCProxy\ViewVCProxy;
use ProjectManager;

class RepositoryDisplayController
{

    /** @var SvnPermissionManager */
    private $permissions_manager;

    public function __construct(
        RepositoryManager $repository_manager,
        ProjectManager $project_manager,
        SvnPermissionManager $permissions_manager
    ) {
        $this->permissions_manager = $permissions_manager;
        $this->repository_manager  = $repository_manager;
        $this->proxy               = new ViewVCProxy($repository_manager, $project_manager);
    }

    public function displayRepository(ServiceSvn $service, HTTPRequest $request)
    {
        try {
            $repository = $this->repository_manager->getById($request->get('repo_id'), $request->getProject());
            $service->renderInPage(
                $request,
                $GLOBALS['Language']->getText('plugin_svn', 'descriptor_name'),
                'explorer/repository_display',
                new RepositoryDisplayPresenter($repository, $request, $this->proxy->getContent($request), $this->permissions_manager)
            );
        } catch (RepositoryNotFoundException $e) {
            $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('plugin_svn', 'repository_not_found'));
            $GLOBALS['Response']->redirect(SVN_BASE_URL.'/?'. http_build_query(array('group_id' => $request->getProject()->getid())));
        }
    }
}
