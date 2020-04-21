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

use PHPUnit\Framework\TestCase;
use Tuleap\Authentication\Scope\AuthenticationScope;
use Tuleap\Authentication\Scope\AuthenticationScopeBuilder;
use Tuleap\Authentication\Scope\AuthenticationScopeIdentifier;
use Tuleap\ForgeConfigSandbox;
use Tuleap\Language\LocaleSwitcher;
use Tuleap\OAuth2Server\OAuth2TestScope;

final class SwaggerJsonOAuth2SecurityDefinitionTest extends TestCase
{
    use ForgeConfigSandbox;

    public function testOAuth2SecurityDefinitionHasTheExpectedFormat(): void
    {
        \ForgeConfig::set('sys_https_host', 'example.com');
        $scopes_builder = new /** @psalm-immutable */class implements AuthenticationScopeBuilder
        {
            public function buildAuthenticationScopeFromScopeIdentifier(AuthenticationScopeIdentifier $scope_identifier): ?AuthenticationScope
            {
                throw new \LogicException('Not expected in the test');
            }

            public function buildAllAvailableAuthenticationScopes(): array
            {
                return [OAuth2TestScope::fromItself()];
            }
        };

        $security_definition = new SwaggerJsonOAuth2SecurityDefinition($scopes_builder, new LocaleSwitcher());

        $this->assertJsonStringEqualsJsonString(
            '{"type":"oauth2","flow":"accessCode","authorizationUrl":"https:\/\/example.com\/oauth2\/authorize","tokenUrl":"https:\/\/example.com\/oauth2\/token","scopes":{"demo":"Test scope"}}',
            json_encode($security_definition, JSON_THROW_ON_ERROR)
        );
    }
}
