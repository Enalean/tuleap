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

use PHPUnit\Framework\TestCase;

final class AcceptableTenantForAuthenticationConfigurationTest extends TestCase
{
    public function testValueUsedByTheAuthenticationFlowIsTheIdentifierWhenUsersOutsideOfTheSpecificTenantCanAuthenticate(): void
    {
        $common_setup  = AzureADTenantSetup::common();

        $configuration = AcceptableTenantForAuthenticationConfiguration::fromTenantSetupAndTenantID(
            $common_setup,
            'tenant_id'
        );

        $this->assertEquals($common_setup, $configuration->getTenantSetup());
        $this->assertEquals($common_setup->getIdentifier(), $configuration->getValueForAuthenticationFlow());
    }

    public function testValueUsedByTheAuthenticationFlowIsTheTenantIDWhenOnlyUsersFromTheSpecificTenantCanAuthenticate(): void
    {
        $tenant_specific_setup = AzureADTenantSetup::tenantSpecific();

        $configuration = AcceptableTenantForAuthenticationConfiguration::fromTenantSetupAndTenantID(
            $tenant_specific_setup,
            'tenant_id'
        );

        $this->assertEquals($tenant_specific_setup, $configuration->getTenantSetup());
        $this->assertEquals('tenant_id', $configuration->getValueForAuthenticationFlow());
    }

    /**
     * @dataProvider providerSpecificTenantOrganizationsSetup
     */
    public function testAcceptableTenantIssuersIDIsTheTenantIDWhenExpectingUsersFromSpecificTenantOrFromOrganizations(AzureADTenantSetup $tenant_setup): void
    {
        $configuration = AcceptableTenantForAuthenticationConfiguration::fromTenantSetupAndTenantID(
            $tenant_setup,
            'tenant_id'
        );

        $this->assertEquals(['tenant_id'], $configuration->getAcceptableIssuerTenantIDs());
    }

    public function providerSpecificTenantOrganizationsSetup(): array
    {
        return [
            [AzureADTenantSetup::tenantSpecific()],
            [AzureADTenantSetup::organizations()],
        ];
    }

    public function testAcceptableTenantIssuersIDIsEitherTheTenantIDOrAnHardcodedGUIDWhenExpectingAllUsers(): void
    {
        $configuration = AcceptableTenantForAuthenticationConfiguration::fromTenantSetupAndTenantID(
            AzureADTenantSetup::common(),
            'tenant_id'
        );

        $this->assertEquals(['9188040d-6c67-4c5b-b112-36a304b66dad', 'tenant_id'], $configuration->getAcceptableIssuerTenantIDs());
    }

    public function testAcceptableTenantIssuersIDIsTheHardcodedGUIDWhenExpectingOnlyConsumersUsers(): void
    {
        $configuration = AcceptableTenantForAuthenticationConfiguration::fromTenantSetupAndTenantID(
            AzureADTenantSetup::consumers(),
            'tenant_id'
        );

        $this->assertEquals(['9188040d-6c67-4c5b-b112-36a304b66dad'], $configuration->getAcceptableIssuerTenantIDs());
    }
}
