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

namespace Tuleap\OpenIDConnectClient\Provider\AzureADProvider;

use Tuleap\OpenIDConnectClient\Provider\ProviderMalformedDataException;
use Valid_String;

class AzureADProviderManager
{
    /**
     * @var AzureADProviderDao
     */
    private $azure_provider_dao;

    public function __construct(AzureADProviderDao $azure_provider_dao)
    {
        $this->azure_provider_dao  = $azure_provider_dao;
    }

    /**
     * @throws ProviderMalformedDataException
     */
    public function createAzureADProvider(
        string $name,
        string $client_id,
        string $client_secret,
        string $icon,
        string $color,
        string $tenant_id,
        string $tenant_setup_identifier
    ): AzureADProvider {
        $is_unique_authentication_endpoint = false;
        $is_data_valid                     = $this->isAzureProviderDataValid(
            $name,
            $client_id,
            $client_secret,
            $icon,
            $color,
            $tenant_id
        );

        if (! $is_data_valid) {
            throw new ProviderMalformedDataException();
        }

        try {
            $tenant_setup = AzureADTenantSetup::fromIdentifier($tenant_setup_identifier);
        } catch (UnknownAcceptableTenantForAuthenticationIdentifierException $exception) {
            throw new ProviderMalformedDataException('', 0, $exception);
        }

        $id = $this->azure_provider_dao->create(
            $name,
            $client_id,
            $client_secret,
            $icon,
            $color,
            $tenant_id,
            $tenant_setup_identifier
        );

        return new AzureADProvider(
            $id,
            $name,
            $client_id,
            $client_secret,
            $is_unique_authentication_endpoint,
            $icon,
            $color,
            $tenant_id,
            AcceptableTenantForAuthenticationConfiguration::fromTenantSetupAndTenantID(
                $tenant_setup,
                $tenant_id
            )
        );
    }

    public function instantiateAzureProviderFromRow(array $row): AzureADProvider
    {
        return new AzureADProvider(
            (int) $row['id'],
            $row['name'],
            $row['client_id'],
            $row['client_secret'],
            (bool) $row['unique_authentication_endpoint'],
            $row['icon'],
            $row['color'],
            $row['tenant_id'],
            AcceptableTenantForAuthenticationConfiguration::fromTenantSetupAndTenantID(
                AzureADTenantSetup::fromIdentifier($row['acceptable_tenant_auth_identifier']),
                $row['tenant_id']
            )
        );
    }

    private function isAzureProviderDataValid(
        string $name,
        string $client_id,
        string $client_secret,
        string $icon,
        string $color,
        string $tenant_id
    ): bool {
        $string_validator   = new Valid_String();

        return $string_validator->validate($name)
            && $string_validator->validate($client_id)
            && $string_validator->validate($client_secret)
            && $string_validator->validate($tenant_id)
            && $string_validator->validate($icon)
            && $string_validator->validate($color);
    }

    /**
     * @throws ProviderMalformedDataException
     */
    public function updateAzureADProvider(AzureADProvider $provider): void
    {
        $is_data_valid = $this->isAzureProviderDataValid(
            $provider->getName(),
            $provider->getClientId(),
            $provider->getClientSecret(),
            $provider->getIcon(),
            $provider->getColor(),
            $provider->getTenantId()
        );

        if (! $is_data_valid) {
            throw new ProviderMalformedDataException();
        }

        $this->azure_provider_dao->save(
            $provider->getId(),
            $provider->getName(),
            $provider->isUniqueAuthenticationEndpoint(),
            $provider->getClientId(),
            $provider->getClientSecret(),
            $provider->getIcon(),
            $provider->getColor(),
            $provider->getTenantId(),
            $provider->getTenantSetup()->getIdentifier()
        );
    }
}
