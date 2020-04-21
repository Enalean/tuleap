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

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Tuleap\OpenIDConnectClient\Provider\AzureADProvider\AcceptableTenantForAuthenticationConfiguration;
use Tuleap\OpenIDConnectClient\Provider\AzureADProvider\AzureADProvider;
use Tuleap\OpenIDConnectClient\Provider\AzureADProvider\AzureADProviderDao;
use Tuleap\OpenIDConnectClient\Provider\AzureADProvider\AzureADProviderManager;
use Tuleap\OpenIDConnectClient\Provider\AzureADProvider\AzureADTenantSetup;
use Tuleap\OpenIDConnectClient\Provider\GenericProvider\GenericProvider;
use Tuleap\OpenIDConnectClient\Provider\GenericProvider\GenericProviderDao;
use Tuleap\OpenIDConnectClient\Provider\GenericProvider\GenericProviderManager;

class ProviderManagerTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var GenericProviderManager|\Mockery\MockInterface|GenericProviderDao
     */
    private $generic_provider_manager;
    /**
     * @var AzureADProviderManager|\Mockery\MockInterface|AzureADProviderDao
     */
    private $azure_provider_manager;
    /**
     * @var ProviderManager
     */
    private $provider_manager;
    /**
     * @var ProviderDao|\Mockery\MockInterface|ProviderDao
     */
    private $provider_dao;

    /**
     * @var AzureADProvider
     */
    private $azure_provider;
    /**
     * @var GenericProvider
     */
    private $generic_provider;

    public function setUp(): void
    {
        $this->generic_provider_manager = \Mockery::mock(GenericProviderManager::class);
        $this->azure_provider_manager   = \Mockery::mock(AzureADProviderManager::class);
        $this->provider_dao             = \Mockery::mock(ProviderDao::class);
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
            'tenant_id' => 'tenant'
        ];
        $this->provider_dao->shouldReceive('searchById')->withArgs([42])->andReturn($data_row);
        $this->azure_provider_manager
            ->shouldReceive('instantiateAzureProviderFromRow')
            ->withArgs([$data_row])
            ->andReturn($this->azure_provider);

        $this->provider_manager->getById(42);
    }

    public function testGetByIdCallGenericProvider(): void
    {
        $data_row =    [
            'id' => 42,
            'pas tenant id' => 'pas tenant'
        ];

        $this->provider_dao->shouldReceive('searchById')->withArgs([42])->andReturn($data_row);
        $this->generic_provider_manager
            ->shouldReceive('instantiateGenericProviderFromRow')
            ->withArgs([$data_row])
            ->andReturn($this->generic_provider);

        $this->provider_manager->getById(42);
    }

    public function testGetByIdCallTrowErrorIfInvalidParameters(): void
    {
        $this->provider_dao->shouldReceive('searchById')->withArgs([42])->andReturn(false);

        $this->expectException('Tuleap\OpenIDConnectClient\Provider\ProviderNotFoundException');
        $this->provider_manager->getById(42);
    }
}
