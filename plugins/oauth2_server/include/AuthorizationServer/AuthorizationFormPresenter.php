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

/**
 * @psalm-immutable
 */
final class AuthorizationFormPresenter
{
    /**
     * @var int
     */
    public $app_id;
    /**
     * @var string
     */
    public $app_name;
    /**
     * @var string
     */
    public $project_name;
    /**
     * @var UriInterface
     */
    public $deny_authorization_uri;
    /**
     * @var \CSRFSynchronizerToken
     */
    public $csrf_token;
    /**
     * @var string | null
     */
    public $state;
    /**
     * @var string
     */
    public $redirect_uri;
    /**
     * @var OAuth2ScopeDefinitionPresenter[]
     */
    public $scope_definition_presenters;
    /**
     * @var OAuth2ScopeIdentifierPresenter[]
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

        $this->app_id                      = $app->getId();
        $this->app_name                    = $app->getName();
        $this->project_name                = $app->getProject()->getPublicName();
        $this->csrf_token                  = $data->getCSRFToken();
        $this->state                       = $data->getState();
        $this->redirect_uri                = $data->getRedirectUri();
        $this->deny_authorization_uri      = $deny_authorization_uri;
        $this->scope_definition_presenters = $scope_definition_presenters;
        $this->scope_identifier_presenters = $scope_identifier_presenters;
    }
}
