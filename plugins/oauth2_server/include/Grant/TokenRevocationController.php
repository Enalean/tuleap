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
use Tuleap\Cryptography\ConcealedString;
use Tuleap\OAuth2Server\AccessToken\OAuth2AccessTokenRevoker;
use Tuleap\OAuth2Server\App\OAuth2App;
use Tuleap\OAuth2Server\OAuth2ServerException;
use Tuleap\OAuth2Server\RefreshToken\OAuth2RefreshTokenRevoker;
use Tuleap\Request\DispatchablePSR15Compatible;
use Tuleap\Request\DispatchableWithRequestNoAuthz;
use Tuleap\User\OAuth2\OAuth2Exception;

final class TokenRevocationController extends DispatchablePSR15Compatible implements DispatchableWithRequestNoAuthz
{
    private const CONTENT_TYPE_RESPONSE = 'application/json;charset=UTF-8';

    private const ERROR_CODE_INVALID_REQUEST = 'invalid_request';
    private const ERROR_CODE_INVALID_CLIENT  = 'invalid_client';
    private const TOKEN_PARAMETER            = 'token';
    /**
     * @var ResponseFactoryInterface
     */
    private $response_factory;
    /**
     * @var StreamFactoryInterface
     */
    private $stream_factory;
    /**
     * @var OAuth2RefreshTokenRevoker
     */
    private $refresh_token_revoker;
    /**
     * @var OAuth2AccessTokenRevoker
     */
    private $access_token_revoker;

    public function __construct(
        ResponseFactoryInterface $response_factory,
        StreamFactoryInterface $stream_factory,
        OAuth2RefreshTokenRevoker $refresh_token_revoker,
        OAuth2AccessTokenRevoker $access_token_revoker,
        EmitterInterface $emitter,
        MiddlewareInterface ...$middleware_stack
    ) {
        parent::__construct($emitter, ...$middleware_stack);
        $this->response_factory      = $response_factory;
        $this->stream_factory        = $stream_factory;
        $this->refresh_token_revoker = $refresh_token_revoker;
        $this->access_token_revoker  = $access_token_revoker;
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $app = $request->getAttribute(OAuth2ClientAuthenticationMiddleware::class);
        if (! $app instanceof OAuth2App) {
            return $this->buildErrorResponse(self::ERROR_CODE_INVALID_CLIENT);
        }

        $body_params = $request->getParsedBody();

        if (! is_array($body_params) || ! isset($body_params[self::TOKEN_PARAMETER])) {
            return $this->buildErrorResponse(self::ERROR_CODE_INVALID_REQUEST);
        }

        $token_identifier = new ConcealedString($body_params[self::TOKEN_PARAMETER]);
        try {
            $this->refresh_token_revoker->revokeGrantOfRefreshToken($app, $token_identifier);
        } catch (SplitTokenException $e) {
            $this->access_token_revoker->revokeGrantOfAccessToken($app, $token_identifier);
        } catch (OAuth2ServerException | OAuth2Exception $e) {
            // Invalid tokens do not cause an error response
            // see https://tools.ietf.org/html/rfc7009#section-2.2
            // Just ignore the error to go into finally
        } finally {
            // Invalid tokens do not cause an error response
            // see https://tools.ietf.org/html/rfc7009#section-2.2
            return $this->response_factory->createResponse(200);
        }
    }

    /**
     * @psalm-param self::ERROR_CODE_* $error_code https://tools.ietf.org/html/rfc7009#section-2.2.1
     */
    private function buildErrorResponse(string $error_code): ResponseInterface
    {
        if ($error_code === self::ERROR_CODE_INVALID_CLIENT) {
            $response = $this->response_factory->createResponse(401)
                ->withHeader('WWW-Authenticate', 'Basic realm="Tuleap OAuth2 Token Revocation Endpoint"');
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
