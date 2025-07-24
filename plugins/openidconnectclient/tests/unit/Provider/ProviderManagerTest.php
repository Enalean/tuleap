<?php
/**
 * Copyright (c) Enalean, 2019 - Present. All Rights Reserved.
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
use Tuleap\OpenIDConnectClient\Provider\AzureADProvider\AzureADProviderManager;
use Tuleap\OpenIDConnectClient\Provider\AzureADProvider\AzureADTenantSetup;
use Tuleap\OpenIDConnectClient\Provider\GenericProvider\GenericProvider;
use Tuleap\OpenIDConnectClient\Provider\GenericProvider\GenericProviderManager;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class ProviderManagerTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private ProviderManager $provider_manager;

    private AzureADProvider $azure_provider;
    private GenericProvider $generic_provider;
    private GenericProviderManager&\PHPUnit\Framework\MockObject\MockObject $generic_provider_manager;
    private AzureADProviderManager&\PHPUnit\Framework\MockObject\MockObject $azure_provider_manager;
    private ProviderDao&\PHPUnit\Framework\MockObject\MockObject $provider_dao;

    #[\Override]
    public function setUp(): void
    {
        $this->generic_provider_manager = $this->createMock(GenericProviderManager::class);
        $this->azure_provider_manager   = $this->createMock(AzureADProviderManager::class);
        $this->provider_dao             = $this->createMock(ProviderDao::class);
        $this->provider_manager         = new ProviderManager(
            $this->provider_dao,
            $this->generic_provider_manager,
            $this->azure_provider_manager
        );

        $this->azure_provider = new AzureADProvider(
            42,
            'Provider',
            'Id Client',
            'secret',
            false,
            'github',
            'fiesta_red',
            'tenant',
            AcceptableTenantForAuthenticationConfiguration::fromTenantSetupAndTenantID(AzureADTenantSetup::common(), 'tenant')
        );

        $this->generic_provider = new GenericProvider(
            42,
            'Provider',
            'https://example.com/auth',
            'https://example.com/token',
            'https://example.com/jwks',
            'https://example.com/userinfo',
            'Id Client',
            'secret',
            false,
            'github',
            'fiesta_red'
        );
    }

    public function testGetByIdCallAzureADProvider(): void
    {
        $data_row =    [
            'id' => 42,
            'tenant_id' => 'tenant',
        ];
        $this->provider_dao->method('searchById')->with(42)->willReturn($data_row);
        $this->azure_provider_manager
            ->method('instantiateAzureProviderFromRow')
            ->with($data_row)
            ->willReturn($this->azure_provider);

        self::assertSame($this->azure_provider, $this->provider_manager->getById(42));
    }

    public function testGetByIdCallGenericProvider(): void
    {
        $data_row =    [
            'id' => 42,
            'pas tenant id' => 'pas tenant',
        ];

        $this->provider_dao->method('searchById')->with(42)->willReturn($data_row);
        $this->generic_provider_manager
            ->method('instantiateGenericProviderFromRow')
            ->with($data_row)
            ->willReturn($this->generic_provider);

        self::assertSame($this->generic_provider, $this->provider_manager->getById(42));
    }

    public function testGetByIdCallTrowErrorIfInvalidParameters(): void
    {
        $this->provider_dao->method('searchById')->with(42)->willReturn(null);

        $this->expectException(\Tuleap\OpenIDConnectClient\Provider\ProviderNotFoundException::class);
        $this->provider_manager->getById(42);
    }
}
