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
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Tuleap\OAuth2Server\App\OAuth2App;
use Tuleap\OAuth2Server\Grant\AuthorizationCode\OAuth2GrantAccessTokenFromAuthorizationCode;
use Tuleap\OAuth2Server\Grant\RefreshToken\OAuth2GrantAccessTokenFromRefreshToken;
use Tuleap\Request\DispatchablePSR15Compatible;
use Tuleap\Request\DispatchableWithRequestNoAuthz;

final class AccessTokenGrantController extends DispatchablePSR15Compatible implements DispatchableWithRequestNoAuthz
{
    public const CONTENT_TYPE_RESPONSE = 'application/json;charset=UTF-8';

    private const GRANT_TYPE_PARAMETER     = 'grant_type';
    private const GRANT_AUTHORIZATION_CODE = 'authorization_code';
    private const GRANT_REFRESH_TOKEN      = 'refresh_token';

    /**
     * @var AccessTokenGrantErrorResponseBuilder
     */
    private $access_token_grant_error_response_builder;
    /**
     * @var OAuth2GrantAccessTokenFromAuthorizationCode
     */
    private $access_token_from_authorization_code;
    /**
     * @var OAuth2GrantAccessTokenFromRefreshToken
     */
    private $access_token_from_refresh_token;

    public function __construct(
        AccessTokenGrantErrorResponseBuilder $access_token_grant_error_response_builder,
        OAuth2GrantAccessTokenFromAuthorizationCode $access_token_from_authorization_code,
        OAuth2GrantAccessTokenFromRefreshToken $access_token_from_refresh_token,
        EmitterInterface $emitter,
        MiddlewareInterface ...$middleware_stack
    ) {
        parent::__construct($emitter, ...$middleware_stack);
        $this->access_token_grant_error_response_builder = $access_token_grant_error_response_builder;
        $this->access_token_from_authorization_code      = $access_token_from_authorization_code;
        $this->access_token_from_refresh_token           = $access_token_from_refresh_token;
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $app = $request->getAttribute(OAuth2ClientAuthenticationMiddleware::class);
        if (! $app instanceof OAuth2App) {
            return $this->access_token_grant_error_response_builder->buildInvalidClientResponse();
        }

        $body_params = $request->getParsedBody();

        if (! is_array($body_params) || ! isset($body_params[self::GRANT_TYPE_PARAMETER])) {
            return $this->access_token_grant_error_response_builder->buildInvalidRequestResponse();
        }

        if ($body_params[self::GRANT_TYPE_PARAMETER] === self::GRANT_AUTHORIZATION_CODE) {
            return $this->access_token_from_authorization_code->grantAccessToken($app, $body_params);
        }
        if ($body_params[self::GRANT_TYPE_PARAMETER] === self::GRANT_REFRESH_TOKEN) {
            return $this->access_token_from_refresh_token->grantAccessToken($app, $body_params);
        }

        return $this->access_token_grant_error_response_builder->buildInvalidGrantResponse();
    }
}
