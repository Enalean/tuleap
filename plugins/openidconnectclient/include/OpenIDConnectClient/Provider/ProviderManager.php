<?php
/**
 * Copyright (c) Enalean, 2016 - present. All Rights Reserved.
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

namespace Tuleap\OpenIDConnectClient\Provider;

use Tuleap\OpenIDConnectClient\Provider\AzureADProvider\AzureADProviderManager;
use Tuleap\OpenIDConnectClient\Provider\GenericProvider\GenericProviderManager;

class ProviderManager
{
    /**
     * @var ProviderDao
     */
    private $dao;

    /**
     * @var GenericProviderManager
     */
    private $generic_provider_manager;

    /**
     * @var AzureADProviderManager
     */
    private $azure_provider_manager;

    public function __construct(
        ProviderDao $dao,
        GenericProviderManager $generic_provider_manager,
        AzureADProviderManager $azure_provider_manager
    ) {
        $this->dao                      = $dao;
        $this->generic_provider_manager = $generic_provider_manager;
        $this->azure_provider_manager   = $azure_provider_manager;
    }

    /**
     * @throws ProviderNotFoundException
     */
    public function getById($id): Provider
    {
        $row = $this->dao->searchById($id);
        if ($row === false) {
            throw new ProviderNotFoundException();
        }
        if (isset($row['tenant_id'])) {
            return $this->azure_provider_manager->instantiateAzureProviderFromRow($row);
        }

        return $this->generic_provider_manager->instantiateGenericProviderFromRow($row);
    }

    public function remove(Provider $provider): void
    {
        $this->dao->deleteById($provider->getId());
    }

    /**
     * @return Provider[]
     */
    public function getProvidersUsableToLogIn(): array
    {
        $providers = array();
        $rows      = $this->dao->searchProvidersUsableToLogIn();
        if ($rows === false) {
            return $providers;
        }

        return $this->extractProviderFromRows($rows, $providers);
    }

    /**
     * @return Provider[]
     */
    public function getProviders(): array
    {
        $providers = array();
        $rows      = $this->dao->searchProviders();
        if ($rows === false) {
            return $providers;
        }

        return $this->extractProviderFromRows($rows, $providers);
    }

    public function isAProviderConfiguredAsUniqueAuthenticationEndpoint(): bool
    {
        return $this->dao->isAProviderConfiguredAsUniqueEndPointProvider();
    }

    /**
     * @param $rows
     * @param array $providers
     * @return array
     */
    private function extractProviderFromRows($rows, array $providers): array
    {
        foreach ($rows as $row) {
            if (isset($row['tenant_id'])) {
                $providers[] = $this->azure_provider_manager->instantiateAzureProviderFromRow($row);
                continue;
            }
            $providers[] = $this->generic_provider_manager->instantiateGenericProviderFromRow($row);
        }
        return $providers;
    }
}
