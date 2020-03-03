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

namespace Tuleap\OAuth2Server\Grant;

use Laminas\HttpHandlerRunner\Emitter\EmitterInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Server\MiddlewareInterface;
use Tuleap\Authentication\SplitToken\SplitTokenException;
use Tuleap\Authentication\SplitToken\SplitTokenIdentifierTranslator;
use Tuleap\Cryptography\ConcealedString;
use Tuleap\OAuth2Server\App\OAuth2App;
use Tuleap\OAuth2Server\Grant\AuthorizationCode\AuthorizationCodeGrantResponseBuilder;
use Tuleap\OAuth2Server\Grant\AuthorizationCode\OAuth2AuthorizationCodeVerifier;
use Tuleap\OAuth2Server\OAuth2ServerException;
use Tuleap\Request\DispatchablePSR15Compatible;
use Tuleap\Request\DispatchableWithRequestNoAuthz;

final class AccessTokenGrantController extends DispatchablePSR15Compatible implements DispatchableWithRequestNoAuthz
{
    private const CONTENT_TYPE_RESPONSE = 'application/json;charset=UTF-8';

    private const GRANT_TYPE_PARAMETER    = 'grant_type';
    private const AUTH_CODE_PARAMETER     = 'code';
    private const REDIRECT_URI_PARAMETER  = 'redirect_uri';
    private const ALLOWED_GRANT_TYPES     = ['authorization_code'];

    private const ERROR_CODE_INVALID_REQUEST = 'invalid_request';
    private const ERROR_CODE_INVALID_GRANT   = 'invalid_grant';
    private const ERROR_CODE_INVALID_CLIENT  = 'invalid_client';

    /**
     * @var ResponseFactoryInterface
     */
    private $response_factory;
    /**
     * @var StreamFactoryInterface
     */
    private $stream_factory;
    /**
     * @var AuthorizationCodeGrantResponseBuilder
     */
    private $response_builder;
    /**
     * @var SplitTokenIdentifierTranslator
     */
    private $access_token_identifier_unserializer;
    /**
     * @var OAuth2AuthorizationCodeVerifier
     */
    private $authorization_code_verifier;

    public function __construct(
        ResponseFactoryInterface $response_factory,
        StreamFactoryInterface $stream_factory,
        AuthorizationCodeGrantResponseBuilder $response_builder,
        SplitTokenIdentifierTranslator $access_token_identifier_unserializer,
        OAuth2AuthorizationCodeVerifier $authorization_code_verifier,
        EmitterInterface $emitter,
        MiddlewareInterface ...$middleware_stack
    ) {
        parent::__construct($emitter, ...$middleware_stack);
        $this->response_factory                     = $response_factory;
        $this->stream_factory                       = $stream_factory;
        $this->response_builder                     = $response_builder;
        $this->access_token_identifier_unserializer = $access_token_identifier_unserializer;
        $this->authorization_code_verifier          = $authorization_code_verifier;
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $app = $request->getAttribute(OAuth2ClientAuthenticationMiddleware::class);
        if (! $app instanceof OAuth2App) {
            return $this->buildErrorResponse(self::ERROR_CODE_INVALID_CLIENT);
        }

        $body_params = $request->getParsedBody();

        if (! is_array($body_params) || ! isset($body_params[self::GRANT_TYPE_PARAMETER])) {
            return $this->buildErrorResponse(self::ERROR_CODE_INVALID_REQUEST);
        }

        if (! in_array($body_params[self::GRANT_TYPE_PARAMETER], self::ALLOWED_GRANT_TYPES, true)) {
            return $this->buildErrorResponse(self::ERROR_CODE_INVALID_GRANT);
        }

        if (! isset($body_params[self::AUTH_CODE_PARAMETER])) {
            return $this->buildErrorResponse(self::ERROR_CODE_INVALID_REQUEST);
        }

        try {
            $authorization_code = $this->authorization_code_verifier->getAuthorizationCode(
                $this->access_token_identifier_unserializer->getSplitToken(new ConcealedString($body_params[self::AUTH_CODE_PARAMETER]))
            );
        } catch (OAuth2ServerException|SplitTokenException $exception) {
            return $this->buildErrorResponse(self::ERROR_CODE_INVALID_GRANT);
        } finally {
            \sodium_memzero($body_params[self::AUTH_CODE_PARAMETER]);
        }

        if (! isset($body_params[self::REDIRECT_URI_PARAMETER])) {
            return $this->buildErrorResponse(self::ERROR_CODE_INVALID_REQUEST);
        }
        if (! \hash_equals($app->getRedirectEndpoint(), $body_params[self::REDIRECT_URI_PARAMETER])) {
            return $this->buildErrorResponse(self::ERROR_CODE_INVALID_GRANT);
        }

        $representation = $this->response_builder->buildResponse(
            new \DateTimeImmutable(),
            $authorization_code
        );

        return $this->response_factory->createResponse()
            ->withHeader('Content-Type', self::CONTENT_TYPE_RESPONSE)
            ->withBody(
                $this->stream_factory->createStream(
                    json_encode($representation, JSON_THROW_ON_ERROR)
                )
            );
    }

    /**
     * @psalm-param self::ERROR_CODE_* $error_code See https://tools.ietf.org/html/rfc6749#section-5.2
     */
    private function buildErrorResponse(string $error_code): ResponseInterface
    {
        if ($error_code === self::ERROR_CODE_INVALID_CLIENT) {
            $response = $this->response_factory->createResponse(401)
                ->withHeader('WWW-Authenticate', 'Basic realm="Tuleap OAuth2 Token Endpoint"');
        } else {
            $response = $this->response_factory->createResponse(400);
        }
        return $response
            ->withHeader('Content-Type', self::CONTENT_TYPE_RESPONSE)
            ->withBody(
                $this->stream_factory->createStream(json_encode(['error' => $error_code], JSON_THROW_ON_ERROR))
            );
    }
}
