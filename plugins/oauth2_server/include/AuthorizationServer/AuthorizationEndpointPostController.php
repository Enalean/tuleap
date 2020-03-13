<?php
/**
 * Copyright (c) Enalean, 2020-Present. All Rights Reserved.
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

declare(strict_types=1);

namespace Tuleap\OAuth2Server\AuthorizationServer;

use Laminas\HttpHandlerRunner\Emitter\EmitterInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Tuleap\OAuth2Server\User\AuthorizationCreator;
use Tuleap\OAuth2Server\User\NewAuthorization;
use Tuleap\Request\DispatchablePSR15Compatible;
use Tuleap\Request\ForbiddenException;
use Tuleap\User\OAuth2\Scope\OAuth2ScopeIdentifier;

final class AuthorizationEndpointPostController extends DispatchablePSR15Compatible
{
    // We can name those however we want, they are not constrained by the spec.
    private const REDIRECT_URI = 'redirect_uri';
    private const STATE        = 'state';
    private const APP_ID       = 'app_id';
    private const SCOPE        = 'scope';

    /**
     * @var \UserManager
     */
    private $user_manager;
    /**
     * @var AuthorizationCreator
     */
    private $authorization_creator;
    /**
     * @var AuthorizationCodeResponseFactory
     */
    private $response_creator;
    /**
     * @var \CSRFSynchronizerToken
     */
    private $csrf_token;

    public function __construct(
        \UserManager $user_manager,
        AuthorizationCreator $authorization_creator,
        AuthorizationCodeResponseFactory $response_creator,
        \CSRFSynchronizerToken $csrf_token,
        EmitterInterface $emitter,
        MiddlewareInterface ...$middleware_stack
    ) {
        parent::__construct($emitter, ...$middleware_stack);
        $this->user_manager          = $user_manager;
        $this->authorization_creator = $authorization_creator;
        $this->response_creator      = $response_creator;
        $this->csrf_token            = $csrf_token;
    }

    /**
     * @throws ForbiddenException
     */
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $user = $this->user_manager->getCurrentUser();
        if ($user->isAnonymous()) {
            throw new ForbiddenException();
        }
        $body_params = $this->getValidBodyParameters($request);
        $this->csrf_token->check();

        $scope_identifiers = [];
        foreach ($body_params[self::SCOPE] as $scope_key) {
            $scope_identifiers[] = OAuth2ScopeIdentifier::fromIdentifierKey($scope_key);
        }
        $new_authorization = new NewAuthorization($user, (int) $body_params[self::APP_ID], ...$scope_identifiers);
        $this->authorization_creator->saveAuthorization($new_authorization);

        $redirect_uri = $body_params[self::REDIRECT_URI];
        $state_value  = $body_params[self::STATE] ?? null;
        return $this->response_creator->createSuccessfulResponse($user, $redirect_uri, $state_value);
    }

    /**
     * @psalm-return array{redirect_uri:string, app_id:mixed, scope:string[]}
     * @throws ForbiddenException
     */
    private function getValidBodyParameters(ServerRequestInterface $request): array
    {
        $body_params = $request->getParsedBody();
        if (! is_array($body_params)
            || ! isset($body_params[self::REDIRECT_URI])
            || ! is_string($body_params[self::REDIRECT_URI])
            || ! isset($body_params[self::APP_ID])
            || filter_var($body_params[self::APP_ID], FILTER_VALIDATE_INT) === false
            || ! isset($body_params[self::SCOPE])
        ) {
            throw new ForbiddenException();
        }
        $this->validateStringArray($body_params[self::SCOPE]);
        return $body_params;
    }

    /**
     * @psalm-assert string[] $scope_keys
     * @throws ForbiddenException
     */
    public function validateStringArray(array $scope_keys)
    {
        foreach ($scope_keys as $scope_key) {
            if (! is_string($scope_key)) {
                throw new ForbiddenException();
            }
        }
    }
}
