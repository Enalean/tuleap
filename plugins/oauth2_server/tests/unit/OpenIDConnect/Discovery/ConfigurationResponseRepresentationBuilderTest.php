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

namespace Tuleap\OAuth2Server\OpenIDConnect\Discovery;

use Tuleap\Authentication\Scope\AuthenticationScopeBuilder;
use Tuleap\ForgeConfigSandbox;
use Tuleap\OAuth2ServerCore\OpenIDConnect\Scope\OAuth2SignInScope;
use Tuleap\OAuth2ServerCore\OpenIDConnect\Scope\OpenIDConnectEmailScope;
use Tuleap\OAuth2ServerCore\OpenIDConnect\Scope\OpenIDConnectProfileScope;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class ConfigurationResponseRepresentationBuilderTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use ForgeConfigSandbox;

    public function testBuildsRepresentation(): void
    {
        \ForgeConfig::set('sys_default_domain', 'tuleap.example.com');
        $language_factory = $this->createMock(\BaseLanguageFactory::class);
        $language_factory
            ->expects(self::once())
            ->method('getAvailableLanguages')
            ->willReturn(
                [
                    'ja_JP' => '日本語',
                    'en_US' => 'English',
                    'fr_FR' => 'Français',
                ]
            );
        $scope_builder = $this->createMock(AuthenticationScopeBuilder::class);
        $scope_builder->expects(self::once())->method('buildAllAvailableAuthenticationScopes')
            ->willReturn(
                [
                    OAuth2SignInScope::fromItself(),
                    OpenIDConnectEmailScope::fromItself(),
                    OpenIDConnectProfileScope::fromItself(),
                ]
            );
        $builder        = new ConfigurationResponseRepresentationBuilder($language_factory, $scope_builder);
        $representation = $builder->build();

        $expected = [
            'issuer'                                => 'https://tuleap.example.com',
            'authorization_endpoint'                => 'https://tuleap.example.com/oauth2/authorize',
            'token_endpoint'                        => 'https://tuleap.example.com/oauth2/token',
            'userinfo_endpoint'                     => 'https://tuleap.example.com/oauth2/userinfo',
            'jwks_uri'                              => 'https://tuleap.example.com/oauth2/jwks',
            'scopes_supported'                      => ['openid', 'email', 'profile'],
            'response_types_supported'              => ['code'],
            'response_modes_supported'              => ['query'],
            'grant_types_supported'                 => ['authorization_code'],
            'subject_types_supported'               => ['public'],
            'id_token_signing_alg_values_supported' => ['RS256'],
            'userinfo_signing_alg_values_supported' => ['none'],
            'token_endpoint_auth_methods_supported' => ['client_secret_basic'],
            'service_documentation'                 => 'https://tuleap.example.com/doc/en/user-guide/oauth2.html',
            'ui_locales_supported'                  => ['ja-JP', 'en-US', 'fr-FR'],
            'claims_parameter_supported'            => false,
            'request_parameter_supported'           => false,
            'request_uri_parameter_supported'       => false,
            'require_request_uri_registration'      => false,
            'op_tos_uri'                            => 'https://tuleap.example.com/tos/tos.php',
            'claims_supported'                      => [
                'sub',
                'iss',
                'aud',
                'iat',
                'exp',
                'auth_time',
                'email',
                'email_verified',
                'name',
                'preferred_username',
                'profile',
                'picture',
                'zoneinfo',
                'locale',
            ],
        ];

        $this->assertJsonStringEqualsJsonString(
            json_encode($expected, JSON_THROW_ON_ERROR),
            json_encode($representation, JSON_THROW_ON_ERROR)
        );
    }
}
