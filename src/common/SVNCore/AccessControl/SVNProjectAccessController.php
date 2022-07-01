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
use Tuleap\Cryptography\ConcealedString;
use Tuleap\Project\CheckProjectAccess;
use Tuleap\Request\DispatchablePSR15Compatible;

final class SVNProjectAccessController extends DispatchablePSR15Compatible
{
    /**
     * @param SVNAuthenticationMethod[] $svn_authentication_methods
     */
    public function __construct(
        private ResponseFactoryInterface $response_factory,
        private LoggerInterface $logger,
        private \UserManager $user_manager,
        private \ProjectManager $project_factory,
        private CheckProjectAccess $check_project_access,
        private array $svn_authentication_methods,
        EmitterInterface $emitter,
        MiddlewareInterface ...$middleware_stack,
    ) {
        parent::__construct($emitter, ...$middleware_stack);
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $parsed_body = $request->getParsedBody();

        if (
            ! is_array($parsed_body)
            || ! isset($parsed_body['login_name'], $parsed_body['user_secret'], $parsed_body['project_name'])
        ) {
            $this->logger->warning('Received SVN access request is incorrectly formatted');
            return $this->response_factory->createResponse(400);
        }

        $user_secret = new ConcealedString((string) $parsed_body['user_secret']);
        sodium_memzero($parsed_body['user_secret']);

        $login_name = (string) $parsed_body['login_name'];
        $user       = $this->user_manager->getUserByLoginName($login_name);
        if ($user === null) {
            $this->logger->debug(sprintf('Rejected SVN access request: no user with the login name %s', $login_name));
            return $this->buildAccessDeniedResponse();
        }

        $project_name = (string) $parsed_body['project_name'];
        try {
            $project = $this->project_factory->getValidProjectByShortNameOrId($project_name);
        } catch (\Project_NotFoundException $e) {
            $this->logger->debug(sprintf('Rejected SVN access request: project %s is not valid', $project_name), ['exception' => $e]);
            return $this->buildAccessDeniedResponse();
        }
        try {
            $this->check_project_access->checkUserCanAccessProject($user, $project);
        } catch (\Project_AccessException $e) {
            $this->logger->debug(sprintf('Rejected SVN access request: user #%d (%s) cannot access project #%d (%s)', $user->getId(), $user->getUserName(), $project->getID(), $project_name), ['exception' => $e]);
            return $this->buildAccessDeniedResponse();
        }

        foreach ($this->svn_authentication_methods as $authentication_method) {
            if ($authentication_method->isAuthenticated($user, $user_secret, $request)) {
                return $this->buildAccessAllowedResponse();
            }
        }

        $this->logger->debug(sprintf('Rejected SVN access request: no authentication methods was successful for user #%d (%s)', $user->getId(), $user->getUserName()));
        return $this->buildAccessDeniedResponse();
    }

    private function buildAccessDeniedResponse(): ResponseInterface
    {
        return $this->response_factory->createResponse(403);
    }

    private function buildAccessAllowedResponse(): ResponseInterface
    {
        return $this->response_factory->createResponse(204);
    }
}
