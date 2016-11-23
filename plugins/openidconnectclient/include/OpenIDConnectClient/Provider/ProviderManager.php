<?php
/**
 * Copyright (c) Enalean, 2016. All Rights Reserved.
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

namespace Tuleap\OpenIDConnectClient\Provider;


use Valid_HTTPSURI;
use Valid_String;

class ProviderManager {
    /**
     * @var ProviderDao
     */
    private $dao;

    public function __construct(ProviderDao $dao) {
        $this->dao = $dao;
    }

    /**
     * @return Provider
     * @throws ProviderNotFoundException
     */
    public function getById($id) {
        $row = $this->dao->searchById($id);
        if ($row === false) {
            throw new ProviderNotFoundException();
        }
        return $this->instantiateFromRow($row);
    }

    /**
     * @return Provider
     * @throws ProviderDataAccessException
     * @throws ProviderMalformedDataException
     */
    public function create(
        $name,
        $authorization_endpoint,
        $token_endpoint,
        $user_info_endpoint,
        $client_id,
        $client_secret,
        $icon,
        $color
    ) {
        $is_data_valid = $this->isDataValid(
            $name,
            $authorization_endpoint,
            $token_endpoint,
            $user_info_endpoint,
            $client_id,
            $client_secret,
            $icon,
            $color
        );

        if (! $is_data_valid) {
            throw new ProviderMalformedDataException();
        }

        $id = $this->dao->create(
            $name,
            $authorization_endpoint,
            $token_endpoint,
            $user_info_endpoint,
            $client_id,
            $client_secret,
            $icon,
            $color
        );

        if ($id === false) {
            throw new ProviderDataAccessException();
        }

        return new Provider(
            $id,
            $name,
            $authorization_endpoint,
            $token_endpoint,
            $user_info_endpoint,
            $client_id,
            $client_secret,
            false,
            $icon,
            $color
        );
    }

    /**
     * @throws ProviderDataAccessException
     * @throws ProviderMalformedDataException
     */
    public function update(Provider $provider) {
        $is_data_valid = $this->isDataValid(
            $provider->getName(),
            $provider->getAuthorizationEndpoint(),
            $provider->getTokenEndpoint(),
            $provider->getUserInfoEndpoint(),
            $provider->getClientId(),
            $provider->getClientSecret(),
            $provider->getIcon(),
            $provider->getColor()
        );

        if (! $is_data_valid) {
            throw new ProviderMalformedDataException();
        }

        $is_updated = $this->dao->save(
            $provider->getId(),
            $provider->getName(),
            $provider->getAuthorizationEndpoint(),
            $provider->getTokenEndpoint(),
            $provider->getUserInfoEndpoint(),
            $provider->getClientId(),
            $provider->getClientSecret(),
            $provider->getIcon(),
            $provider->getColor()
        );

        if (! $is_updated) {
            throw new ProviderDataAccessException();
        }
    }

    /**
     * @throws ProviderDataAccessException
     */
    public function remove(Provider $provider) {
        $is_deleted = $this->dao->deleteById($provider->getId());
        if (! $is_deleted) {
            throw new ProviderDataAccessException();
        }
    }

    /**
     * @return Provider[]
     */
    public function getProvidersUsableToLogIn() {
        $providers = array();
        $rows      = $this->dao->searchProvidersUsableToLogIn();
        if ($rows === false) {
            return $providers;
        }

        foreach ($rows as $row) {
            $providers[] = $this->instantiateFromRow($row);
        }

        return $providers;
    }

    /**
     * @return Provider[]
     */
    public function getProviders() {
        $providers = array();
        $rows      = $this->dao->searchProviders();
        if ($rows === false) {
            return $providers;
        }

        foreach ($rows as $row) {
            $providers[] = $this->instantiateFromRow($row);
        }

        return $providers;
    }

    /**
     * @return Provider
     */
    private function instantiateFromRow(array $row) {
        return new Provider(
            $row['id'],
            $row['name'],
            $row['authorization_endpoint'],
            $row['token_endpoint'],
            $row['user_info_endpoint'],
            $row['client_id'],
            $row['client_secret'],
            $row['unique_authentication_endpoint'],
            $row['icon'],
            $row['color']
        );
    }

    /**
     * @return bool
     */
    private function isDataValid(
        $name,
        $authorization_endpoint,
        $token_endpoint,
        $userinfo_endpoint,
        $client_id,
        $client_secret,
        $icon,
        $color
    ) {
        $string_validator            = new Valid_String();
        $http_uri_validator          = new Valid_HTTPSURI();
        $http_uri_validator->required();
        $userinfo_endpoint_validator = new Valid_HTTPSURI();

        return $string_validator->validate($name) && $string_validator->validate($client_id) &&
            $string_validator->validate($client_secret) && $http_uri_validator->validate($authorization_endpoint) &&
            $http_uri_validator->validate($token_endpoint) &&
            $userinfo_endpoint_validator->validate($userinfo_endpoint) &&
            $string_validator->validate($icon) && $string_validator->validate($color);
    }

}
