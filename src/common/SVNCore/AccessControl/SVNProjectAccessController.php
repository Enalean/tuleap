<?php
/**
 * Copyright (c) Enalean, 2022-Present. All Rights Reserved.
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

declare(strict_types=1);

namespace Tuleap\SVNCore\AccessControl;

use Laminas\HttpHandlerRunner\Emitter\EmitterInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Log\LoggerInterface;
use Tuleap\Http\Server\Authentication\BasicAuthLoginExtractor;
use Tuleap\Project\CheckProjectAccess;
use Tuleap\Request\DispatchablePSR15Compatible;
use Tuleap\Request\DispatchableWithRequestNoAuthz;
use Tuleap\SVNCore\Cache\ParameterRetriever as CacheParameterRetriever;

/**
 * This controller when a user tries to access a SVN repository. It is called indirectly using the ngx_http_auth_request_module
 * nginx module via the internal nginx location `/svn-project-auth`.
 *
 *                  ┌───────────┐          ┌──────────────────────────────┐                    ┌──────────────┐        ┌──────────────────┐
 *                  │           │          │                              │                    │              │        │                  │
 * SVN Request ────►│   nginx   ├─────────►│ ngx_http_auth_request_module ├───────────────────►│    Apache    ├───────►│  SVN repository  │
 *                  │           │          │                              │                    │              │        │                  │
 *                  └───────────┘          └────────┬─────────────────────┘                    └──┬───────────┘        └──────────────────┘
 *                                                  │           ▲                                 │       ▲
 *                                /svn-project-auth ▼           │                                 │       │
 *                                         ┌────────────────────┴─────────┐                       ▼       │
 *                                         │                              │                 ┌─────────────┴──────┐
 *                                         │                              │                 │                    │
 *                                         │   nginx internal location    │                 │ AuthzSVNAccessFile │
 *                                         │                              │                 │                    │
 *                                         │                              │                 └─────┬──────────────┘
 *                                         └────────┬─────────────────────┘                       │       ▲
 *                                                  │           ▲                                 │       │
 *       POST /svnroot/<project_name>               │           │                                 ▼       │
 *       POST /svnplugin/<project_name>/<repo_name> ▼           │                             ┌───────────┴────┐
 *                                          ┌───────────────────┴────────┐                    │ .SVNAccessFile │
 *                                          │                            │                    └────────────────┘
 *                                          │ SVNProjectAccessController │
 *                                          │                            │
 *                                          └────────────────────────────┘
 */
final class SVNProjectAccessController extends DispatchablePSR15Compatible implements DispatchableWithRequestNoAuthz
{
    /**
     * @param SVNAuthenticationMethod[] $svn_authentication_methods
     */
    public function __construct(
        private ResponseFactoryInterface $response_factory,
        private LoggerInterface $logger,
        private BasicAuthLoginExtractor $basic_auth_login_extractor,
        private \ProjectManager $project_factory,
        private CheckProjectAccess $check_project_access,
        private array $svn_authentication_methods,
        private CacheParameterRetriever $cache_parameter_retriever,
        EmitterInterface $emitter,
        MiddlewareInterface ...$middleware_stack,
    ) {
        parent::__construct($emitter, ...$middleware_stack);
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $credentials_set = $this->basic_auth_login_extractor->extract($request);
        if ($credentials_set === null) {
            $this->logger->debug('Received SVN access request is incorrectly formatted, no credentials found');
            return $this->buildAuthenticationRequiredResponse();
        }

        $project_name = $request->getAttribute('project_name', '');
        if ($project_name === '') {
            $this->logger->warning('Received SVN access request is incorrectly formatted, missing project name');
            return $this->response_factory->createResponse(400);
        }
        $project_name = $request->getAttribute('project_name');

        try {
            $project = $this->project_factory->getValidProjectByShortNameOrId($project_name);
        } catch (\Project_NotFoundException $e) {
            $this->logger->debug(sprintf('Rejected SVN access request: project %s is not valid', $project_name), ['exception' => $e]);
            return $this->buildAccessDeniedResponse();
        }

        if (! $project->isActive()) {
            $this->logger->debug(sprintf('Rejected SVN access request: project %s is not active', $project_name));
            return $this->buildAccessDeniedResponse();
        }

        $user        = null;
        $login_name  = $credentials_set->getUsername();
        $user_secret = $credentials_set->getPassword();
        foreach ($this->svn_authentication_methods as $authentication_method) {
            $user = $authentication_method->isAuthenticated($login_name, $user_secret, $project, $request);
            if ($user !== null) {
                break;
            }
        }

        if ($user === null) {
            $this->logger->debug(sprintf('Rejected SVN access request: could not authenticate user with the login name %s', $login_name));
            return $this->buildAuthenticationRequiredResponse();
        }

        try {
            $this->check_project_access->checkUserCanAccessProject($user, $project);
        } catch (\Project_AccessException $e) {
            $this->logger->debug(sprintf('Rejected SVN access request: user #%d (%s) cannot access project #%d (%s)', $user->getId(), $user->getUserName(), $project->getID(), $project_name), ['exception' => $e]);
            return $this->buildAccessDeniedResponse();
        }

        return $this->buildAccessAllowedResponse();
    }

    private function buildAuthenticationRequiredResponse(): ResponseInterface
    {
        return $this->response_factory->createResponse(401)
            ->withHeader('WWW-Authenticate', 'Basic realm="Authentication is required to access the repository."');
    }

    private function buildAccessDeniedResponse(): ResponseInterface
    {
        return $this->response_factory->createResponse(403);
    }

    private function buildAccessAllowedResponse(): ResponseInterface
    {
        $cache_parameters       = $this->cache_parameter_retriever->getParameters();
        $cache_lifetime_seconds = $cache_parameters->getLifetime() * 60;

        return $this->response_factory->createResponse(204)
            ->withHeader('Cache-Control', 'max-age=' . $cache_lifetime_seconds);
    }
}
