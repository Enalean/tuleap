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

use Psr\Http\Message\UriInterface;
use Tuleap\OAuth2Server\App\ClientIdentifier;

final class AuthorizationFormPresenter
{
    /**
     * @var string
     */
    public $app_identifier;
    /**
     * @var string
     * @psalm-readonly
     */
    public $app_name;
    /**
     * @var string
     * @psalm-readonly
     */
    public $project_name;
    /**
     * @var UriInterface
     * @psalm-readonly
     */
    public $deny_authorization_uri;
    /**
     * @var \CSRFSynchronizerToken
     * @psalm-readonly
     */
    public $csrf_token;
    /**
     * @var string | null
     * @psalm-readonly
     */
    public $state;
    /**
     * @var string|null
     * @psalm-readonly
     */
    public $pkce_code_challenge;
    /**
     * @var string|null
     * @psalm-readonly
     */
    public $oidc_nonce;
    /**
     * @var string
     * @psalm-readonly
     */
    public $redirect_uri;
    /**
     * @var OAuth2ScopeDefinitionPresenter[]
     * @psalm-readonly
     */
    public $scope_definition_presenters;
    /**
     * @var OAuth2ScopeIdentifierPresenter[]
     * @psalm-readonly
     */
    public $scope_identifier_presenters;

    /**
     * @psalm-param list<OAuth2ScopeDefinitionPresenter> $scope_definition_presenters
     * @psalm-param list<OAuth2ScopeIdentifierPresenter> $scope_identifier_presenters
     */
    public function __construct(
        AuthorizationFormData $data,
        UriInterface $deny_authorization_uri,
        array $scope_definition_presenters,
        array $scope_identifier_presenters
    ) {
        $app = $data->getApp();

        $this->app_identifier              = ClientIdentifier::fromOAuth2App($app)->toString();
        $this->app_name                    = $app->getName();
        $this->project_name                = $app->getProject()->getPublicName();
        $this->csrf_token                  = $data->getCSRFToken();
        $this->state                       = $data->getState();
        $pkce_code_challenge               = $data->getPKCECodeChallenge();
        $this->pkce_code_challenge         = $pkce_code_challenge === null ? null : bin2hex($pkce_code_challenge);
        $this->oidc_nonce                  = $data->getOIDCNonce();
        $this->redirect_uri                = $data->getRedirectUri();
        $this->deny_authorization_uri      = $deny_authorization_uri;
        $this->scope_definition_presenters = $scope_definition_presenters;
        $this->scope_identifier_presenters = $scope_identifier_presenters;
    }
}
