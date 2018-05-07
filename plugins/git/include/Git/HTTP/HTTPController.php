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

namespace Tuleap\Git\HTTP;

use HTTPRequest;
use Tuleap\Git\Gerrit\ReplicationHTTPUserAuthenticator;
use Tuleap\Git\Gitolite\VersionDetector;
use Tuleap\Git\RemoteServer\Gerrit\HttpUserValidator;
use Tuleap\Layout\BaseLayout;
use Tuleap\Request\DispatchableWithRequestNoAuthz;
use Tuleap\Request\ForbiddenException;
use Tuleap\Request\NotFoundException;

class HTTPController implements DispatchableWithRequestNoAuthz
{

    /**
     * @var \GitRepositoryFactory
     */
    private $repository_factory;
    /**
     * @var \Logger
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
        \Logger $logger,
        \ProjectManager $project_manager,
        \GitRepositoryFactory $repository_factory,
        \Git_RemoteServer_GerritServerFactory $gerrit_server_factory,
        \PermissionsManager $permissions_manager
    ) {
        $this->project_manager       = $project_manager;
        $this->repository_factory    = $repository_factory;
        $this->logger                = new \WrapperLogger($logger, 'http');

        $password_handler = \PasswordHandlerFactory::getPasswordHandler();
        $this->http_access_control = new HTTPAccessControl(
            $this->logger,
            new \User_LoginManager(
                \EventManager::instance(),
                \UserManager::instance(),
                new \User_PasswordExpirationChecker(),
                $password_handler
            ),
            new ReplicationHTTPUserAuthenticator(
                $password_handler,
                $gerrit_server_factory,
                new HttpUserValidator()
            ),
            $permissions_manager
        );

        $this->http_command_factory = new \Git_HTTP_CommandFactory(
            new VersionDetector()
        );
    }

    /**
     * @param \URLVerification $url_verification
     * @param HTTPRequest $request
     * @param array $variables

     * @throws NotFoundException
     * @throws ForbiddenException

     * @return bool
     */
    public function userCanAccess(\URLVerification $url_verification, HTTPRequest $request, array $variables)
    {
        \Tuleap\Instrument\Collect::increment(\GitPlugin::INSTRUMENTATION_KEY);

        $this->repository = $this->repository_factory->getByProjectNameAndPath(
            $variables['project_name'],
            $this->getRepoPathWithFinalDotGit($variables['path'])
        );
        if (! $this->repository) {
            throw new NotFoundException(dgettext('tuleap-git', 'Repository does not exist'));
        }

        $this->url = new \Git_URL(
            $this->project_manager,
            $this->repository_factory,
            $_SERVER['REQUEST_URI']
        );

        $this->user = $this->http_access_control->getUser($url_verification, $this->repository, $this->url);
        if ($this->user === false) {
            throw new ForbiddenException(dgettext('tuleap-git', 'User cannot access repository'));
        }

        return true;
    }

    /**
     * Is able to process a request routed by FrontRouter
     *
     * @param HTTPRequest $request
     * @param BaseLayout $layout
     * @param array $variables
     *
     * @return void
     */
    public function process(HTTPRequest $request, BaseLayout $layout, array $variables)
    {
        $http_wrapper = new \Git_HTTP_Wrapper($this->logger);
        $http_wrapper->stream($this->http_command_factory->getCommandForUser($this->url, $this->user));
    }

    private function getRepoPathWithFinalDotGit($path)
    {
        if (substr($path, strlen($path) - 4) !== '.git') {
            return $path.'.git';
        }
        return $path;
    }
}
