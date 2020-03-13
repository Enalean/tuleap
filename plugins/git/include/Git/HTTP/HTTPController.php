<?php
/**
 * Copyright (c) Enalean, 2018-2019. All Rights Reserved.
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

namespace Tuleap\Git\HTTP;

use HTTPRequest;
use Project;
use Tuleap\Git\Gitolite\VersionDetector;
use Tuleap\Layout\BaseLayout;
use Tuleap\Request\DispatchableWithProject;
use Tuleap\Request\DispatchableWithRequestNoAuthz;
use Tuleap\Request\ForbiddenException;
use Tuleap\Request\NotFoundException;

class HTTPController implements DispatchableWithRequestNoAuthz, DispatchableWithProject
{

    /**
     * @var \GitRepositoryFactory
     */
    private $repository_factory;
    /**
     * @var \Psr\Log\LoggerInterface
     */
    private $logger;
    /**
     * @var \ProjectManager
     */
    private $project_manager;

    /**
     * @var \GitRepository
     */
    private $repository;
    /**
     * @var \Git_URL
     */
    private $url;
    /**
     * @var \PFO_User|null
     */
    private $user;
    /**
     * @var HTTPAccessControl
     */
    private $http_access_control;
    /**
     * @var \Git_HTTP_CommandFactory
     */
    private $http_command_factory;

    public function __construct(
        \Psr\Log\LoggerInterface $logger,
        \ProjectManager $project_manager,
        \GitRepositoryFactory $repository_factory,
        HTTPAccessControl $http_access_control
    ) {
        $this->project_manager       = $project_manager;
        $this->repository_factory    = $repository_factory;
        $this->logger                = new \WrapperLogger($logger, 'http');
        $this->http_access_control   = $http_access_control;

        $this->http_command_factory = new \Git_HTTP_CommandFactory(
            new VersionDetector()
        );
    }

    /**
     * Return the project that corresponds to current URI
     *
     * @param array $variables
     *
     * @throws NotFoundException
     */
    public function getProject(array $variables) : Project
    {
        $project = $this->project_manager->getProjectByCaseInsensitiveUnixName($variables['project_name']);
        if (! $project || $project->isError()) {
            throw new NotFoundException(dgettext("tuleap-git", "Project not found."));
        }

        return $project;
    }

    private function checkUserCanAccess(array $variables)
    {
        \Tuleap\Project\ServiceInstrumentation::increment('git');

        $this->repository = $this->repository_factory->getByProjectNameAndPath(
            $variables['project_name'],
            $this->getRepoPathWithFinalDotGit($variables['path'])
        );
        if (! $this->repository) {
            throw new NotFoundException(dgettext('tuleap-git', 'Repository does not exist'));
        }

        $project = $this->repository->getProject();
        if ($project->getStatus() === Project::STATUS_SUSPENDED) {
            throw new ForbiddenException(dgettext('tuleap-git', 'Project is not active'));
        }

        $this->url = new \Git_URL(
            $this->project_manager,
            $this->repository_factory,
            $_SERVER['REQUEST_URI']
        );

        $this->user = $this->http_access_control->getUser($this->repository, $this->url);
        if ($this->user === false) {
            throw new ForbiddenException(dgettext('tuleap-git', 'User cannot access repository'));
        }
    }

    /**
     * Is able to process a request routed by FrontRouter
     *
     * @param array $variables
     *
     * @return void
     */
    public function process(HTTPRequest $request, BaseLayout $layout, array $variables)
    {
        $this->checkUserCanAccess($variables);

        $http_wrapper = new \Git_HTTP_Wrapper($this->logger);
        $http_wrapper->stream($this->http_command_factory->getCommandForUser($this->url, $this->user));
    }

    private function getRepoPathWithFinalDotGit($path)
    {
        if (substr($path, strlen($path) - 4) !== '.git') {
            return $path . '.git';
        }
        return $path;
    }
}
