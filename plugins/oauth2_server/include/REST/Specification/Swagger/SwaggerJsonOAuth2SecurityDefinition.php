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

namespace Tuleap\OAuth2Server\REST\Specification\Swagger;

use Tuleap\Authentication\Scope\AuthenticationScopeBuilder;
use Tuleap\Language\LocaleSwitcher;
use Tuleap\OAuth2Server\OpenIDConnect\Issuer;
use Tuleap\REST\Specification\Swagger\SwaggerJsonSecurityDefinition;

/**
 * @see https://github.com/OAI/OpenAPI-Specification/blob/v3.0.1/versions/2.0.md#securitySchemeObject
 */
final class SwaggerJsonOAuth2SecurityDefinition implements SwaggerJsonSecurityDefinition
{
    /**
     * @var string
     * @psalm-readonly
     */
    public $type = 'oauth2';
    /**
     * @var string
     * @psalm-readonly
     */
    public $flow = 'accessCode';
    /**
     * @var string
     * @psalm-readonly
     */
    public $authorizationUrl;
    /**
     * @var string
     * @psalm-readonly
     */
    public $tokenUrl;
    /**
     * @var string[]
     * @psalm-var array<string,string>
     * @psalm-readonly
     */
    public $scopes = [];

    public function __construct(AuthenticationScopeBuilder $scope_builder, LocaleSwitcher $locale_switcher)
    {
        $issuer                 = Issuer::toString();
        $this->authorizationUrl = $issuer . '/oauth2/authorize';
        $this->tokenUrl         = $issuer . '/oauth2/token';

        $locale_switcher->setLocaleForSpecificExecutionContext(
            'en_US',
            function () use ($scope_builder): void {
                foreach ($scope_builder->buildAllAvailableAuthenticationScopes() as $scope) {
                    $this->scopes[$scope->getIdentifier()->toString()] = $scope->getDefinition()->getName();
                }
            }
        );
    }
}
