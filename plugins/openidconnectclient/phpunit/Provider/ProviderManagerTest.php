<?php
/**
 * Copyright (c) Enalean, 2016 - Present. All Rights Reserved.
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

require_once(__DIR__ . '/../bootstrap.php');

class ProviderManagerTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    public function testItCreatesNewProvider(): void
    {
        $generic_provider_dao = \Mockery::mock(GenericProviderDao::class);
        $provider_dao         = \Mockery::mock(ProviderDao::class);
        $provider_manager     = new ProviderManager($provider_dao, $generic_provider_dao);

        $generic_provider_dao->shouldReceive('create')->andReturn(1)->once();

        $provider_manager->createGenericProvider(
            'Provider',
            'https://example.com/auth',
            'https://example.com/token',
            'https://example.com/userinfo',
            'ID',
            'Secret',
            'github',
            'fiesta_red'
        );
    }

    public function testItCreatesNewProviderWithAnEmptyUserInfoEndpoint(): void
    {
        $generic_provider_dao = \Mockery::mock(GenericProviderDao::class);
        $provider_dao         = \Mockery::mock(ProviderDao::class);
        $provider_manager     = new ProviderManager($provider_dao, $generic_provider_dao);

        $generic_provider_dao->shouldReceive('create')->andReturn(1)->once();

        $provider_manager->createGenericProvider(
            'Provider',
            'https://example.com/auth',
            'https://example.com/token',
            '',
            'ID',
            'Secret',
            'github',
            'fiesta_red'
        );
    }

    public function testItUpdatesProvider(): void
    {
        $generic_provider_dao = \Mockery::mock(GenericProviderDao::class);
        $provider_dao         = \Mockery::mock(ProviderDao::class);
        $provider_manager     = new ProviderManager($provider_dao, $generic_provider_dao);
        $provider             = new GenericProvider(
            0,
            'Provider',
            'https://example.com/auth',
            'https://example.com/token',
            'https://example.com/userinfo',
            'ID',
            'Secret',
            false,
            'github',
            'fiesta_red'
        );

        $generic_provider_dao->shouldReceive('save')->once()->andReturns(true);

        $provider_manager->update($provider);
    }

    public function testItChecksDataBeforeManipulatingAProvider(): void
    {
        $generic_provider_dao = \Mockery::mock(GenericProviderDao::class);
        $provider_dao         = \Mockery::mock(ProviderDao::class);
        $provider_manager     = new ProviderManager($provider_dao, $generic_provider_dao);

        $generic_provider_dao->shouldReceive('create')->never();
        $generic_provider_dao->shouldReceive('save')->never();
        $this->expectException('Tuleap\OpenIDConnectClient\Provider\ProviderMalformedDataException');

        $provider = new GenericProvider(
            0,
            'Provider',
            'Not A URL',
            'Not A URL',
            'Not A URL',
            'ID',
            'Secret',
            false,
            'github',
            'fiesta_red'
        );

        $provider_manager->createGenericProvider(
            $provider->getName(),
            $provider->getAuthorizationEndpoint(),
            $provider->getTokenEndpoint(),
            $provider->getUserInfoEndpoint(),
            $provider->getClientId(),
            $provider->getClientSecret(),
            $provider->getIcon(),
            $provider->getColor()
        );
        $provider_manager->update($provider);
    }
}
