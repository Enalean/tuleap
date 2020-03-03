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
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Tuleap\Cryptography\ConcealedString;
use Tuleap\Request\DispatchablePSR15Compatible;
use Tuleap\Request\ForbiddenException;

final class AuthorizationEndpointPostController extends DispatchablePSR15Compatible
{
    // We can name those however we want, they are not constrained by the spec.
    private const REDIRECT_URI = 'redirect_uri';
    private const STATE        = 'state';
    /**
     * @var ResponseFactoryInterface
     */
    private $response_factory;
    /**
     * @var \UserManager
     */
    private $user_manager;
    /**
     * @var RedirectURIBuilder
     */
    private $client_uri_redirect_builder;
    /**
     * @var \CSRFSynchronizerToken
     */
    private $csrf_token;

    public function __construct(
        ResponseFactoryInterface $response_factory,
        \UserManager $user_manager,
        RedirectURIBuilder $client_uri_redirect_builder,
        \CSRFSynchronizerToken $csrf_token,
        EmitterInterface $emitter,
        MiddlewareInterface ...$middleware_stack
    ) {
        parent::__construct($emitter, ...$middleware_stack);
        $this->response_factory            = $response_factory;
        $this->user_manager                = $user_manager;
        $this->client_uri_redirect_builder = $client_uri_redirect_builder;
        $this->csrf_token                  = $csrf_token;
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
        $body_params = $request->getParsedBody();
        if (! is_array($body_params) || ! isset($body_params[self::REDIRECT_URI])) {
            throw new ForbiddenException();
        }
        $this->csrf_token->check();

        $redirect_uri = $body_params[self::REDIRECT_URI];
        $state_value  = isset($body_params[self::STATE]) ? $body_params[self::STATE] : null;
        $authorization_code = new ConcealedString(
            'tlp-oauth2-ac1-1.6161616161616161616161616161616161616161616161616161616161616161'
        );

        $success_redirect_uri = $this->client_uri_redirect_builder->buildSuccessURI(
            $redirect_uri,
            $state_value,
            $authorization_code
        );
        return $this->response_factory->createResponse(302)
            ->withHeader('Location', (string) $success_redirect_uri);
    }
}
