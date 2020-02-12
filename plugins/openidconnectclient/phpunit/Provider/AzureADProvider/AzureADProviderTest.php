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

final class AzureADProviderTest extends TestCase
{
    public function testAuthenticationAndTokenEndpointsAreBuiltFromTheAcceptableTenantForAuthenticationConfiguration(): void
    {
        $acceptable_tenant_setup = AzureADTenantSetup::consumers();

        $provider = new AzureADProvider(
            1,
            'MyProvider',
            'client_id',
            'client_secret',
            false,
            'icon',
            'color',
            'tenant_id',
            AcceptableTenantForAuthenticationConfiguration::fromTenantSetupAndTenantID(
                $acceptable_tenant_setup,
                'tenant_id'
            )
        );

        $this->assertStringContainsString($acceptable_tenant_setup->getIdentifier(), $provider->getAuthorizationEndpoint());
        $this->assertStringContainsString($acceptable_tenant_setup->getIdentifier(), $provider->getTokenEndpoint());
    }

    public function testGetFindAcceptableIssuerTenantIDs(): void
    {
        $provider = new AzureADProvider(
            1,
            'MyProvider',
            'client_id',
            'client_secret',
            false,
            'icon',
            'color',
            'tenant_id',
            AcceptableTenantForAuthenticationConfiguration::fromTenantSetupAndTenantID(
                AzureADTenantSetup::tenantSpecific(),
                'tenant_id'
            )
        );

        $this->assertEquals(['tenant_id'], $provider->getAcceptableIssuerTenantIDs());
    }
}
