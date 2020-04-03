<?php
/**
 * Copyright (c) Enalean, 2019 - present. All Rights Reserved.
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

namespace Tuleap\OpenIDConnectClient\Provider\GenericProvider;

use Tuleap\OpenIDConnectClient\Provider\Provider;
use Tuleap\OpenIDConnectClient\Provider\ProviderMalformedDataException;
use Valid_HTTPSURI;
use Valid_String;

class GenericProviderManager
{
    /**
     * @var GenericProviderDao
     */
    private $generic_provider_dao;

    public function __construct(GenericProviderDao $generic_provider_dao)
    {
        $this->generic_provider_dao  = $generic_provider_dao;
    }
    /**
     * @throws ProviderMalformedDataException
     */
    public function createGenericProvider(
        string $name,
        string $authorization_endpoint,
        string $token_endpoint,
        string $user_info_endpoint,
        string $client_id,
        string $client_secret,
        string $icon,
        string $color
    ): GenericProvider {
        $is_unique_authentication_endpoint = false;
        $is_data_valid                     = $this->isGenericProviderDataValid(
            $name,
            $authorization_endpoint,
            $token_endpoint,
            $user_info_endpoint,
            $is_unique_authentication_endpoint,
            $client_id,
            $client_secret,
            $icon,
            $color
        );

        if (! $is_data_valid) {
            throw new ProviderMalformedDataException();
        }

        $id = $this->generic_provider_dao->create(
            $name,
            $authorization_endpoint,
            $token_endpoint,
            $user_info_endpoint,
            $client_id,
            $client_secret,
            $icon,
            $color
        );

        return new GenericProvider(
            $id,
            $name,
            $authorization_endpoint,
            $token_endpoint,
            $user_info_endpoint,
            $client_id,
            $client_secret,
            $is_unique_authentication_endpoint,
            $icon,
            $color
        );
    }

    /**
     * @throws ProviderMalformedDataException
     */
    public function updateGenericProvider(Provider $provider)
    {
        $is_data_valid = $this->isGenericProviderDataValid(
            $provider->getName(),
            $provider->getAuthorizationEndpoint(),
            $provider->getTokenEndpoint(),
            $provider->getUserInfoEndpoint(),
            $provider->isUniqueAuthenticationEndpoint(),
            $provider->getClientId(),
            $provider->getClientSecret(),
            $provider->getIcon(),
            $provider->getColor()
        );

        if (! $is_data_valid) {
            throw new ProviderMalformedDataException();
        }

        $this->generic_provider_dao->save(
            $provider->getId(),
            $provider->getName(),
            $provider->getAuthorizationEndpoint(),
            $provider->getTokenEndpoint(),
            $provider->getUserInfoEndpoint(),
            $provider->isUniqueAuthenticationEndpoint(),
            $provider->getClientId(),
            $provider->getClientSecret(),
            $provider->getIcon(),
            $provider->getColor()
        );
    }

    private function isGenericProviderDataValid(
        string $name,
        string $authorization_endpoint,
        string $token_endpoint,
        string $userinfo_endpoint,
        bool $is_unique_authentication_endpoint,
        string $client_id,
        string $client_secret,
        string $icon,
        string $color
    ): bool {
        $string_validator   = new Valid_String();
        $http_uri_validator = new Valid_HTTPSURI();
        $http_uri_validator->required();
        $userinfo_endpoint_validator = new Valid_HTTPSURI();

        return $string_validator->validate($name)
            && $string_validator->validate($client_id)
            && $string_validator->validate($client_secret)
            && $http_uri_validator->validate($authorization_endpoint)
            && $http_uri_validator->validate($token_endpoint)
            && $userinfo_endpoint_validator->validate($userinfo_endpoint)
            && is_bool($is_unique_authentication_endpoint)
            && $string_validator->validate($icon)
            && $string_validator->validate($color);
    }

    public function instantiateGenericProviderFromRow(array $row): GenericProvider
    {
        return new GenericProvider(
            (int) $row['id'],
            $row['name'],
            $row['authorization_endpoint'],
            $row['token_endpoint'],
            $row['user_info_endpoint'],
            $row['client_id'],
            $row['client_secret'],
            (bool) $row['unique_authentication_endpoint'],
            $row['icon'],
            $row['color']
        );
    }
}
