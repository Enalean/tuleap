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

namespace Tuleap\User\OAuth2\Scope;

use Luracast\Restler\Data\ApiMethodInfo;
use Tuleap\Authentication\Scope\AuthenticationScope;
use Tuleap\Authentication\Scope\AuthenticationScopeBuilder;

final class OAuth2ScopeExtractorRESTEndpoint
{
    private const ANNOTATION_NAME = 'oauth2-scope';

    /**
     * @var AuthenticationScopeBuilder
     */
    private $authentication_scope_builder;

    public function __construct(AuthenticationScopeBuilder $authentication_scope_builder)
    {
        $this->authentication_scope_builder = $authentication_scope_builder;
    }

    /**
     * @psalm-return AuthenticationScope<OAuth2ScopeIdentifier>
     *
     * @throws NoOAuth2ScopeOnRESTEndpointException
     * @throws OAuth2ScopeRESTEndpointInvalidException
     */
    public function extractRequiredScope(ApiMethodInfo $api_method_info): AuthenticationScope
    {
        if (! isset($api_method_info->metadata[self::ANNOTATION_NAME])) {
            throw new NoOAuth2ScopeOnRESTEndpointException($api_method_info);
        }

        try {
            $scope_identifier = OAuth2ScopeIdentifier::fromIdentifierKey($api_method_info->metadata[self::ANNOTATION_NAME]);
        } catch (InvalidOAuth2ScopeIdentifierException $exception) {
            throw OAuth2ScopeRESTEndpointInvalidException::invalidIdentifierKey($api_method_info, $exception);
        }

        $scope = $this->authentication_scope_builder->buildAuthenticationScopeFromScopeIdentifier(
            $scope_identifier
        );
        if ($scope === null) {
            throw OAuth2ScopeRESTEndpointInvalidException::scopeNotFound($api_method_info, $scope_identifier);
        }
        return $scope;
    }
}
