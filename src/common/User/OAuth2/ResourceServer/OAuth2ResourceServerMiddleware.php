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

namespace Tuleap\User\OAuth2\ResourceServer;

use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Tuleap\Authentication\SplitToken\SplitTokenException;
use Tuleap\Authentication\SplitToken\SplitTokenIdentifierTranslator;
use Tuleap\User\OAuth2\AccessToken\OAuth2AccessTokenVerifier;
use Tuleap\User\OAuth2\BearerTokenHeaderParser;
use Tuleap\User\OAuth2\OAuth2Exception;
use User_LoginException;

final class OAuth2ResourceServerMiddleware implements MiddlewareInterface
{
    /**
     * @var ResponseFactoryInterface
     */
    private $response_factory;
    /**
     * @var BearerTokenHeaderParser
     */
    private $bearer_token_header_parser;
    /**
     * @var SplitTokenIdentifierTranslator
     */
    private $access_token_identifier_unserializer;
    /**
     * @var OAuth2AccessTokenVerifier
     */
    private $access_token_verifier;
    /**
     * @var \User_LoginManager
     */
    private $login_manager;

    public function __construct(
        ResponseFactoryInterface $response_factory,
        BearerTokenHeaderParser $bearer_token_header_parser,
        SplitTokenIdentifierTranslator $access_token_identifier_unserializer,
        OAuth2AccessTokenVerifier $access_token_verifier,
        \User_LoginManager $login_manager
    ) {
        $this->response_factory                   = $response_factory;
        $this->bearer_token_header_parser         = $bearer_token_header_parser;
        $this->access_token_identifier_unserializer = $access_token_identifier_unserializer;
        $this->access_token_verifier              = $access_token_verifier;
        $this->login_manager                      = $login_manager;
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler) : ResponseInterface
    {
        $authorization_header = $request->getHeaderLine('Authorization');

        $serialized_access_token_identifier = $this->bearer_token_header_parser->parseHeaderLine($authorization_header);

        if ($serialized_access_token_identifier === null) {
            return $this->response_factory->createResponse(401);
        }

        try {
            $user = $this->access_token_verifier->getUser(
                $this->access_token_identifier_unserializer->getSplitToken($serialized_access_token_identifier)
            );
        } catch (OAuth2Exception|SplitTokenException $exception) {
            return $this->response_factory->createResponse(401);
        }

        try {
            $this->login_manager->validateAndSetCurrentUser($user);
        } catch (User_LoginException $exception) {
            return $this->response_factory->createResponse(401);
        }

        return $handler->handle($request);
    }
}
