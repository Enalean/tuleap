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
use Tuleap\Authentication\Scope\AuthenticationScope;
use Tuleap\Layout\BaseLayout;
use Tuleap\OAuth2Server\App\AppFactory;
use Tuleap\OAuth2Server\App\ClientIdentifier;
use Tuleap\OAuth2Server\App\InvalidClientIdentifierKey;
use Tuleap\OAuth2Server\App\OAuth2App;
use Tuleap\OAuth2Server\App\OAuth2AppNotFoundException;
use Tuleap\OAuth2Server\AuthorizationServer\PKCE\OAuth2PKCEInformationExtractionException;
use Tuleap\OAuth2Server\AuthorizationServer\PKCE\PKCEInformationExtractor;
use Tuleap\OAuth2Server\RefreshToken\OAuth2OfflineAccessScope;
use Tuleap\OAuth2Server\Scope\InvalidOAuth2ScopeException;
use Tuleap\OAuth2Server\Scope\ScopeExtractor;
use Tuleap\OAuth2Server\User\AuthorizationComparator;
use Tuleap\Request\DispatchablePSR15Compatible;
use Tuleap\Request\DispatchableWithBurningParrot;
use Tuleap\Request\DispatchableWithRequestNoAuthz;
use Tuleap\Request\ForbiddenException;

final class AuthorizationEndpointController extends DispatchablePSR15Compatible implements DispatchableWithBurningParrot, DispatchableWithRequestNoAuthz
{
    // see https://tools.ietf.org/html/rfc6749#section-4.1.1
    private const RESPONSE_TYPE_PARAMETER = 'response_type';
    private const CLIENT_ID_PARAMETER     = 'client_id';
    private const REDIRECT_URI_PARAMETER  = 'redirect_uri';
    public const  SCOPE_PARAMETER         = 'scope';
    public const  CODE_PARAMETER          = 'code';
    public const  STATE_PARAMETER         = 'state';
    // see https://openid.net/specs/openid-connect-core-1_0.html#AuthRequest
    private const NONCE_PARAMETER         = 'nonce';
    public const PROMPT_PARAMETER         = 'prompt';
    private const MAX_AGE_PARAMETER       = 'max_age';
    // see https://tools.ietf.org/html/rfc6749#section-4.1.2.1
    public const  ERROR_PARAMETER            = 'error';
    public const  ERROR_CODE_INVALID_REQUEST = 'invalid_request';
    private const ERROR_CODE_INVALID_SCOPE   = 'invalid_scope';
    public const  ERROR_CODE_ACCESS_DENIED   = 'access_denied';
    // see https://openid.net/specs/openid-connect-core-1_0.html#AuthError
    private const ERROR_CODE_INTERACTION_REQUIRED = 'interaction_required';
    private const ERROR_CODE_LOGIN_REQUIRED       = 'login_required';

    public const CSRF_TOKEN = 'oauth2_server_authorization_endpoint';
    /**
     * @var AuthorizationFormRenderer
     */
    private $form_renderer;
    /**
     * @var \UserManager
     */
    private $user_manager;
    /**
     * @var AppFactory
     */
    private $app_factory;
    /**
     * @var ScopeExtractor
     */
    private $scope_extractor;
    /**
     * @var AuthorizationCodeResponseFactory
     */
    private $response_factory;
    /**
     * @var AuthorizationComparator
     */
    private $authorization_comparator;
    /**
     * @var PKCEInformationExtractor
     */
    private $pkce_information_extractor;
    /**
     * @var PromptParameterValuesExtractor
     */
    private $prompt_parameter_values_extractor;
    /**
     * @var OAuth2OfflineAccessScope
     */
    private $offline_access_scope;

    public function __construct(
        AuthorizationFormRenderer $form_renderer,
        \UserManager $user_manager,
        AppFactory $app_factory,
        ScopeExtractor $scope_extractor,
        AuthorizationCodeResponseFactory $response_factory,
        AuthorizationComparator $authorization_comparator,
        PKCEInformationExtractor $pkce_information_extractor,
        PromptParameterValuesExtractor $prompt_parameter_values_extractor,
        OAuth2OfflineAccessScope $offline_access_scope,
        EmitterInterface $emitter,
        MiddlewareInterface ...$middleware_stack
    ) {
        parent::__construct($emitter, ...$middleware_stack);
        $this->form_renderer                     = $form_renderer;
        $this->user_manager                      = $user_manager;
        $this->app_factory                       = $app_factory;
        $this->scope_extractor                   = $scope_extractor;
        $this->response_factory                  = $response_factory;
        $this->authorization_comparator          = $authorization_comparator;
        $this->pkce_information_extractor        = $pkce_information_extractor;
        $this->prompt_parameter_values_extractor = $prompt_parameter_values_extractor;
        $this->offline_access_scope              = $offline_access_scope;
    }

    /**
     * @throws ForbiddenException
     */
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        if ($request->getMethod() === 'POST') {
            $request_params = $request->getParsedBody();
            if (! \is_array($request_params)) {
                throw new ForbiddenException();
            }
        } else {
            $request_params = $request->getQueryParams();
        }

        $client_id = (string) ($request_params[self::CLIENT_ID_PARAMETER] ?? '');
        try {
            $client_identifier = ClientIdentifier::fromClientId($client_id);
            $client_app        = $this->app_factory->getAppMatchingClientId($client_identifier);
        } catch (InvalidClientIdentifierKey | OAuth2AppNotFoundException $exception) {
            throw new ForbiddenException();
        }

        $redirect_uri = (string) ($request_params[self::REDIRECT_URI_PARAMETER] ?? '');
        if ($redirect_uri !== $client_app->getRedirectEndpoint()) {
            throw new ForbiddenException();
        }

        $state_value = $request_params[self::STATE_PARAMETER] ?? null;
        if (! isset($request_params[self::RESPONSE_TYPE_PARAMETER]) || $request_params[self::RESPONSE_TYPE_PARAMETER] !== self::CODE_PARAMETER) {
            return $this->response_factory->createErrorResponse(
                self::ERROR_CODE_INVALID_REQUEST,
                $redirect_uri,
                $state_value
            );
        }

        try {
            $prompt_values = $this->prompt_parameter_values_extractor->extractPromptValues((string) ($request_params[self::PROMPT_PARAMETER] ?? ''));
        } catch (PromptNoneParameterCannotBeMixedWithOtherPromptParametersException $exception) {
            return $this->response_factory->createErrorResponse(
                self::ERROR_CODE_INVALID_REQUEST,
                $redirect_uri,
                $state_value
            );
        }
        $require_no_interaction = in_array(PromptParameterValuesExtractor::PROMPT_NONE, $prompt_values, true);

        $user = $this->user_manager->getCurrentUser();
        if ($this->doesRequireLogin($user, $prompt_values, $request_params)) {
            if ($require_no_interaction) {
                return $this->response_factory->createErrorResponse(
                    self::ERROR_CODE_LOGIN_REQUIRED,
                    $redirect_uri,
                    $state_value
                );
            }
            return $this->response_factory->createRedirectToLoginResponse($request, $request_params);
        }

        if (! isset($request_params[self::SCOPE_PARAMETER])) {
            return $this->response_factory->createErrorResponse(
                self::ERROR_CODE_INVALID_SCOPE,
                $redirect_uri,
                $state_value
            );
        }
        try {
            $scopes = $this->scope_extractor->extractScopes((string) $request_params[self::SCOPE_PARAMETER]);
        } catch (InvalidOAuth2ScopeException $e) {
            return $this->response_factory->createErrorResponse(
                self::ERROR_CODE_INVALID_SCOPE,
                $redirect_uri,
                $state_value
            );
        }

        try {
            $code_challenge = $this->pkce_information_extractor->extractCodeChallenge($client_app, $request_params);
        } catch (OAuth2PKCEInformationExtractionException $exception) {
            return $this->response_factory->createErrorResponse(
                self::ERROR_CODE_INVALID_REQUEST,
                $redirect_uri,
                $state_value
            );
        }

        $oidc_nonce = $request_params[self::NONCE_PARAMETER] ?? null;

        if (! $this->isConsentRequired($prompt_values, $user, $client_app, $scopes)) {
            return $this->response_factory->createSuccessfulResponse(
                $client_app,
                $scopes,
                $user,
                $redirect_uri,
                $state_value,
                $code_challenge,
                $oidc_nonce
            );
        }

        if ($require_no_interaction) {
            return $this->response_factory->createErrorResponse(
                self::ERROR_CODE_INTERACTION_REQUIRED,
                $redirect_uri,
                $state_value
            );
        }

        $layout = $request->getAttribute(BaseLayout::class);
        assert($layout instanceof BaseLayout);
        $csrf_token = new \CSRFSynchronizerToken(self::CSRF_TOKEN);
        $data       = new AuthorizationFormData($client_app, $csrf_token, $redirect_uri, $state_value, $code_challenge, $oidc_nonce, ...$scopes);
        return $this->form_renderer->renderForm($data, $layout);
    }

    /**
     * @param string[] $prompt_values
     */
    private function doesRequireLogin(\PFUser $user, array $prompt_values, array $request_params): bool
    {
        $require_login = in_array(PromptParameterValuesExtractor::PROMPT_LOGIN, $prompt_values, true);
        if ($require_login || $user->isAnonymous()) {
            return true;
        }

        if (! isset($request_params[self::MAX_AGE_PARAMETER])) {
            return false;
        }

        $current_time   = (new \DateTimeImmutable())->getTimestamp();
        $access_info    = $this->user_manager->getUserAccessInfo($user);
        $last_auth_time = (int) $access_info['last_auth_success'];
        $max_age        = (int) $request_params[self::MAX_AGE_PARAMETER];

        return ($current_time - $last_auth_time) >= $max_age;
    }

    /**
     * @param string[] $prompt_values
     * @param AuthenticationScope[] $scopes
     * @psalm-param non-empty-list<AuthenticationScope<\Tuleap\User\OAuth2\Scope\OAuth2ScopeIdentifier>> $scopes
     */
    private function isConsentRequired(array $prompt_values, \PFUser $user, OAuth2App $client_app, array $scopes): bool
    {
        $require_consent = in_array(PromptParameterValuesExtractor::PROMPT_CONSENT, $prompt_values, true);
        if ($require_consent || ! $this->authorization_comparator->areRequestedScopesAlreadyGranted($user, $client_app, $scopes)) {
            return true;
        }

        foreach ($scopes as $scope) {
            if ($this->offline_access_scope->covers($scope)) {
                return true;
            }
        }

        return false;
    }
}
