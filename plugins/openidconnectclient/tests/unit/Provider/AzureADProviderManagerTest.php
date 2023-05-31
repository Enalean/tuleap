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

namespace Tuleap\OpenIDConnectClient\Provider;

use Tuleap\OpenIDConnectClient\Provider\AzureADProvider\AcceptableTenantForAuthenticationConfiguration;
use Tuleap\OpenIDConnectClient\Provider\AzureADProvider\AzureADProvider;
use Tuleap\OpenIDConnectClient\Provider\AzureADProvider\AzureADProviderDao;
use Tuleap\OpenIDConnectClient\Provider\AzureADProvider\AzureADProviderManager;
use Tuleap\OpenIDConnectClient\Provider\AzureADProvider\AzureADTenantSetup;

final class AzureADProviderManagerTest extends \Tuleap\Test\PHPUnit\TestCase
{
    public function testItCreatesNewAzureProvider(): void
    {
        $azure_provider_dao     = $this->createMock(AzureADProviderDao::class);
        $azure_provider_manager = new AzureADProviderManager($azure_provider_dao);

        $azure_provider_dao->expects(self::once())->method('create')->willReturn(1);

        $tenant_setup = AzureADTenantSetup::common();

        $azure_provider = new AzureADProvider(
            1,
            'Provider',
            'Id Client',
            'secret',
            false,
            'github',
            'fiesta_red',
            'tenant',
            AcceptableTenantForAuthenticationConfiguration::fromTenantSetupAndTenantID($tenant_setup, 'tenant')
        );

        $res = $azure_provider_manager->createAzureADProvider(
            'Provider',
            'Id Client',
            'secret',
            'github',
            'fiesta_red',
            'tenant',
            $tenant_setup->getIdentifier()
        );

        $this->assertEquals($azure_provider, $res);
    }

    public function testItUpdatesProvider(): void
    {
        $generic_provider_dao     = $this->createMock(AzureADProviderDao::class);
        $generic_provider_manager = new AzureADProviderManager(
            $generic_provider_dao
        );

        $tenant_setup = AzureADTenantSetup::common();

        $provider = new AzureADProvider(
            0,
            'Provider',
            'ID',
            'Secret',
            false,
            'github',
            'fiesta_red',
            'tenant id',
            AcceptableTenantForAuthenticationConfiguration::fromTenantSetupAndTenantID($tenant_setup, 'tenant id')
        );

        $generic_provider_dao->expects(self::once())->method('save');
        $generic_provider_manager->updateAzureADProvider($provider);
    }
}
