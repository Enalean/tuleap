<?php
/**
 * Copyright (c) Enalean, 2018. All Rights Reserved.
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
 *
 */

namespace Tuleap\Git;

use GitPlugin;
use HTTPRequest;
use Tuleap\Layout\BaseLayout;
use Tuleap\Request\DispatchableWithRequest;
use Tuleap\Request\ForbiddenException;
use Tuleap\Request\NotFoundException;

class GitLegacyURLRedirectController implements DispatchableWithRequest
{

    /**
     * @var \GitRepositoryFactory
     */
    private $repository_factory;
    /**
     * @var \ProjectManager
     */
    private $project_manager;

    public function __construct(\ProjectManager $project_manager, \GitRepositoryFactory $repository_factory)
    {
        $this->repository_factory = $repository_factory;
        $this->project_manager    = $project_manager;
    }

    /**
     * Is able to process a request routed by FrontRouter
     *
     * @param array $variables
     * @throws NotFoundException
     * @throws ForbiddenException
     * @return void
     */
    public function process(HTTPRequest $request, BaseLayout $layout, array $variables)
    {
        $project = $this->project_manager->getProject($variables['project_id']);
        if (! $project || $project->isError()) {
            throw new NotFoundException();
        }
        if (! $project->usesService(GitPlugin::SERVICE_SHORTNAME)) {
            throw new NotFoundException(dgettext("tuleap-git", "Git service is disabled."));
        }

        $repository = $this->repository_factory->getRepositoryById($variables['repository_id']);
        if (! $repository) {
            throw new NotFoundException();
        }
        if (! $repository->userCanRead($request->getCurrentUser())) {
            throw new ForbiddenException();
        }

        $redirect_url = GIT_BASE_URL . '/' . $repository->getProject()->getUnixName() . '/' . $repository->getFullName();
        if ($_SERVER['QUERY_STRING'] !== '') {
            $redirect_url .= '?' . $_SERVER['QUERY_STRING'];
        }

        $layout->permanentRedirect($redirect_url);
    }
}
