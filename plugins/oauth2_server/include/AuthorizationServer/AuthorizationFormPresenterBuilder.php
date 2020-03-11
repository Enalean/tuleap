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

class AuthorizationFormPresenterBuilder
{
    /**
     * @var RedirectURIBuilder
     */
    private $client_uri_redirect_builder;

    public function __construct(RedirectURIBuilder $client_uri_redirect_builder)
    {
        $this->client_uri_redirect_builder = $client_uri_redirect_builder;
    }

    public function build(AuthorizationFormData $form_data): AuthorizationFormPresenter
    {
        $scope_definition_presenters = [];
        $scope_identifier_presenters = [];
        foreach ($form_data->getScopes() as $scope) {
            $scope_definition_presenters[] = new OAuth2ScopeDefinitionPresenter($scope->getDefinition());
            $scope_identifier_presenters[] = new OAuth2ScopeIdentifierPresenter($scope->getIdentifier());
        }
        $deny_authorization_uri = $this->client_uri_redirect_builder->buildErrorURI(
            $form_data->getRedirectUri(),
            $form_data->getState(),
            AuthorizationEndpointGetController::ERROR_CODE_ACCESS_DENIED
        );
        return new AuthorizationFormPresenter(
            $form_data,
            $deny_authorization_uri,
            $scope_definition_presenters,
            $scope_identifier_presenters
        );
    }
}
