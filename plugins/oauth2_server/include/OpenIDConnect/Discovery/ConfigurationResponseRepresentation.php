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

use Lcobucci\JWT\Signer\Rsa\Sha256;
use Tuleap\OAuth2Server\AuthorizationServer\AuthorizationEndpointController;
use Tuleap\OAuth2Server\OpenIDConnect\Issuer;

/**
 * @see https://openid.net/specs/openid-connect-discovery-1_0.html#ProviderConfigurationResponse
 */
final class ConfigurationResponseRepresentation implements \JsonSerializable
{
    /**
     * @psalm-var non-empty-list<string>
     * @var string[]
     */
    private $supported_scope_identifiers;
    /**
     * @psalm-var non-empty-list<string>
     * @var string[]
     */
    private $supported_languages;

    /**
     * @psalm-param non-empty-list<string> $supported_scope_identifiers
     * @psalm-param non-empty-list<string> $supported_languages
     */
    public function __construct(array $supported_scope_identifiers, array $supported_languages)
    {
        $this->supported_scope_identifiers = $supported_scope_identifiers;
        $this->supported_languages         = $supported_languages;
    }

    public function jsonSerialize()
    {
        $issuer = Issuer::toString();
        // See https://openid.net/specs/openid-connect-discovery-1_0.html#ProviderMetadata
        return [
            'issuer'                                => $issuer,
            'authorization_endpoint'                => $issuer . '/oauth2/authorize',
            'token_endpoint'                        => $issuer . '/oauth2/token',
            'userinfo_endpoint'                     => $issuer . '/oauth2/userinfo',
            'jwks_uri'                              => $issuer . '/oauth2/jwks',
            'scopes_supported'                      => $this->supported_scope_identifiers,
            'response_types_supported'              => [AuthorizationEndpointController::CODE_PARAMETER],
            'response_modes_supported'              => ['query'],
            'grant_types_supported'                 => ['authorization_code'],
            'subject_types_supported'               => ['public'],
            'id_token_signing_alg_values_supported' => [(new Sha256())->getAlgorithmId()],
            'userinfo_signing_alg_values_supported' => ['none'],
            'token_endpoint_auth_methods_supported' => ['client_secret_basic'],
            'service_documentation'                 => $issuer . '/doc/en/',
            'ui_locales_supported'                  => $this->supported_languages,
            'claims_parameter_supported'            => false,
            'request_parameter_supported'           => false,
            'request_uri_parameter_supported'       => false,
            'require_request_uri_registration'      => false,
            'op_tos_uri'                            => $issuer . '/tos/tos.php'

        ];
    }
}
