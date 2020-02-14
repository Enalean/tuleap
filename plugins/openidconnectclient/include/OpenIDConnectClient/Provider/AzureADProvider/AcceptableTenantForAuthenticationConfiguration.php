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

namespace Tuleap\OpenIDConnectClient\Provider\AzureADProvider;

/**
 * @psalm-immutable
 */
final class AcceptableTenantForAuthenticationConfiguration
{
    /**
     * @see https://docs.microsoft.com/en-us/azure/active-directory/develop/access-tokens#payload-claims
     */
    private const TENANT_CONSUMERS_GUID = '9188040d-6c67-4c5b-b112-36a304b66dad';

    /**
     * @var string
     */
    private $tenant_id;
    /**
     * @var AzureADTenantSetup
     */
    private $tenant_setup;

    private function __construct(AzureADTenantSetup $tenant_setup, string $tenant_id)
    {
        $this->tenant_setup = $tenant_setup;
        $this->tenant_id    = $tenant_id;
    }

    public static function fromTenantSetupAndTenantID(
        AzureADTenantSetup $tenant_setup,
        string $tenant_id
    ): self {
        return new self($tenant_setup, $tenant_id);
    }

    public static function fromSpecificTenantID(string $tenant_id): self
    {
        return new self(AzureADTenantSetup::tenantSpecific(), $tenant_id);
    }

    /**
     * @psalm-external-mutation-free
     */
    public function getValueForAuthenticationFlow(): string
    {
        if ($this->tenant_setup === AzureADTenantSetup::tenantSpecific()) {
            return $this->tenant_id;
        }

        return $this->tenant_setup->getIdentifier();
    }

    public function getTenantSetup(): AzureADTenantSetup
    {
        return $this->tenant_setup;
    }

    /**
     * @psalm-external-mutation-free
     *
     * @return string[]
     */
    public function getAcceptableIssuerTenantIDs(): array
    {
        switch ($this->tenant_setup) {
            case AzureADTenantSetup::common():
                return [self::TENANT_CONSUMERS_GUID, $this->tenant_id];
            case AzureADTenantSetup::consumers():
                return [self::TENANT_CONSUMERS_GUID];
            case AzureADTenantSetup::organizations():
            case AzureADTenantSetup::tenantSpecific():
            default:
                return [$this->tenant_id];
        }
    }
}
