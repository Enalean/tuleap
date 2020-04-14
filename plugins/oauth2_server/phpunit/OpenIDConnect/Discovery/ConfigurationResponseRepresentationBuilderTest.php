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

use Mockery as M;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Tuleap\Authentication\Scope\AuthenticationScopeBuilder;
use Tuleap\ForgeConfigSandbox;
use Tuleap\OAuth2Server\OpenIDConnect\Scope\OAuth2SignInScope;
use Tuleap\OAuth2Server\OpenIDConnect\Scope\OpenIDConnectEmailScope;
use Tuleap\OAuth2Server\OpenIDConnect\Scope\OpenIDConnectProfileScope;

final class ConfigurationResponseRepresentationBuilderTest extends TestCase
{
    use ForgeConfigSandbox;
    use MockeryPHPUnitIntegration;

    public function testBuildsRepresentation(): void
    {
        \ForgeConfig::set('sys_https_host', 'tuleap.example.com');
        $language_factory = M::mock(\BaseLanguageFactory::class);
        $language_factory
            ->shouldReceive('getAvailableLanguages')
            ->once()
            ->andReturn(
                [
                    'ja_JP' => '日本語',
                    'en_US' => 'English',
                    'fr_FR' => 'Français'
                ]
            );
        $scope_builder = M::mock(AuthenticationScopeBuilder::class);
        $scope_builder->shouldReceive('buildAllAvailableAuthenticationScopes')
            ->once()
            ->andReturn(
                [
                    OAuth2SignInScope::fromItself(),
                    OpenIDConnectEmailScope::fromItself(),
                    OpenIDConnectProfileScope::fromItself()
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
            'service_documentation'                 => 'https://tuleap.example.com/doc/en/',
            'ui_locales_supported'                  => ['ja-JP', 'en-US', 'fr-FR'],
            'claims_parameter_supported'            => false,
            'request_parameter_supported'           => false,
            'request_uri_parameter_supported'       => false,
            'require_request_uri_registration'      => false,
            'op_tos_uri'                            => 'https://tuleap.example.com/tos/tos.php'
        ];

        $this->assertJsonStringEqualsJsonString(
            json_encode($expected, JSON_THROW_ON_ERROR),
            json_encode($representation, JSON_THROW_ON_ERROR)
        );
    }
}
