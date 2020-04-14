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

use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UriFactoryInterface;
use Tuleap\Authentication\Scope\AuthenticationScope;
use Tuleap\OAuth2Server\App\OAuth2App;
use Tuleap\OAuth2Server\Grant\AuthorizationCode\OAuth2AuthorizationCodeCreator;

class AuthorizationCodeResponseFactory
{
    /**
     * @var ResponseFactoryInterface
     */
    private $response_factory;
    /**
     * @var OAuth2AuthorizationCodeCreator
     */
    private $authorization_code_creator;
    /**
     * @var RedirectURIBuilder
     */
    private $client_uri_redirect_builder;
    /**
     * @var \URLRedirect
     */
    private $login_redirect;
    /**
     * @var UriFactoryInterface
     */
    private $uri_factory;

    public function __construct(
        ResponseFactoryInterface $response_factory,
        OAuth2AuthorizationCodeCreator $authorization_code_creator,
        RedirectURIBuilder $client_uri_redirect_builder,
        \URLRedirect $login_redirect,
        UriFactoryInterface $uri_factory
    ) {
        $this->response_factory            = $response_factory;
        $this->authorization_code_creator  = $authorization_code_creator;
        $this->client_uri_redirect_builder = $client_uri_redirect_builder;
        $this->login_redirect              = $login_redirect;
        $this->uri_factory                 = $uri_factory;
    }

    /**
     * @param AuthenticationScope[] $scopes
     *
     * @psalm-param non-empty-array<AuthenticationScope<\Tuleap\User\OAuth2\Scope\OAuth2ScopeIdentifier>> $scopes
     */
    public function createSuccessfulResponse(
        OAuth2App $app,
        array $scopes,
        \PFUser $user,
        string $redirect_uri,
        ?string $state,
        ?string $pkce_code_challenge,
        ?string $oidc_nonce
    ): ResponseInterface {
        $authorization_code = $this->authorization_code_creator->createAuthorizationCodeIdentifier(
            new \DateTimeImmutable(),
            $app,
            $scopes,
            $user,
            $pkce_code_challenge,
            $oidc_nonce
        );

        $success_redirect_uri = $this->client_uri_redirect_builder->buildSuccessURI(
            $redirect_uri,
            $state,
            $authorization_code
        );
        return $this->response_factory->createResponse(302)->withHeader('Location', (string) $success_redirect_uri);
    }

    /**
     * See https://tools.ietf.org/html/rfc6749#section-4.1.2.1
     * @psalm-param AuthorizationEndpointController::ERROR_CODE_* $error_code
     */
    public function createErrorResponse(string $error_code, string $redirect_uri, ?string $state): ResponseInterface
    {
        $error_uri = $this->client_uri_redirect_builder->buildErrorURI($redirect_uri, $state, $error_code);
        return $this->response_factory->createResponse(302)->withHeader('Location', (string) $error_uri);
    }

    public function createRedirectToLoginResponse(ServerRequestInterface $request, array $request_params): ResponseInterface
    {
        if (isset($request_params[AuthorizationEndpointController::PROMPT_PARAMETER])) {
            $request_params[AuthorizationEndpointController::PROMPT_PARAMETER] = trim(
                str_replace(
                    PromptParameterValuesExtractor::PROMPT_LOGIN,
                    '',
                    $request_params[AuthorizationEndpointController::PROMPT_PARAMETER]
                )
            );
        }

        $url_path = $request->getUri()->getPath();
        $server   = ['REQUEST_URI' => $url_path . '?' . http_build_query($request_params)];

        $return_to_login = $this->uri_factory->createUri($this->login_redirect->buildReturnToLogin($server));
        $return_to_login = $return_to_login->withQuery($return_to_login->getQuery() . '&prompt=login');

        return $this->response_factory->createResponse(302)
            ->withHeader('Location', (string) $return_to_login);
    }
}
