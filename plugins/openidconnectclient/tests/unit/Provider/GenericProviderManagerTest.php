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

use Tuleap\OpenIDConnectClient\Provider\GenericProvider\GenericProvider;
use Tuleap\OpenIDConnectClient\Provider\GenericProvider\GenericProviderDao;
use Tuleap\OpenIDConnectClient\Provider\GenericProvider\GenericProviderManager;

final class GenericProviderManagerTest extends \Tuleap\Test\PHPUnit\TestCase
{
    public function testItCreatesNewGenericProvider(): void
    {
        $generic_provider_dao     = $this->createMock(GenericProviderDao::class);
        $generic_provider_manager = new GenericProviderManager(
            $generic_provider_dao
        );

        $generic_provider_dao->expects(self::once())->method('create')->willReturn(1);

        $generic_provider = new GenericProvider(
            1,
            'Provider',
            'https://example.com/auth',
            'https://example.com/token',
            'https://example.com/jwks',
            'https://example.com/userinfo',
            'Id Client',
            'Secret',
            false,
            'github',
            'fiesta_red'
        );

        $res = $generic_provider_manager->createGenericProvider(
            'Provider',
            'https://example.com/auth',
            'https://example.com/token',
            'https://example.com/jwks',
            'https://example.com/userinfo',
            'Id Client',
            'Secret',
            'github',
            'fiesta_red'
        );

        self::assertEquals($generic_provider, $res);
    }

    public function testItCreatesNewGenericProviderWithAnEmptyUserInfoEndpoint(): void
    {
        $generic_provider_dao     = $this->createMock(GenericProviderDao::class);
        $generic_provider_manager = new GenericProviderManager(
            $generic_provider_dao
        );

        $generic_provider_dao->expects(self::once())->method('create')->willReturn(1);

        $generic_provider = new GenericProvider(
            1,
            'Provider',
            'https://example.com/auth',
            'https://example.com/token',
            'https://example.com/jwks',
            '',
            'Id Client',
            'Secret',
            false,
            'github',
            'fiesta_red'
        );


        $res = $generic_provider_manager->createGenericProvider(
            'Provider',
            'https://example.com/auth',
            'https://example.com/token',
            'https://example.com/jwks',
            '',
            'Id Client',
            'Secret',
            'github',
            'fiesta_red'
        );

        self::assertEquals($generic_provider, $res);
    }

    public function testItCreatesNewGenericProviderWithAnEmptyJWKSEndpoint(): void
    {
        $generic_provider_dao     = $this->createMock(GenericProviderDao::class);
        $generic_provider_manager = new GenericProviderManager(
            $generic_provider_dao
        );

        $generic_provider_dao->expects(self::once())->method('create')->willReturn(1);

        $generic_provider = new GenericProvider(
            1,
            'Provider',
            'https://example.com/auth',
            'https://example.com/token',
            '',
            'https://example.com/userinfo',
            'Id Client',
            'Secret',
            false,
            'github',
            'fiesta_red'
        );


        $res = $generic_provider_manager->createGenericProvider(
            'Provider',
            'https://example.com/auth',
            'https://example.com/token',
            '',
            'https://example.com/userinfo',
            'Id Client',
            'Secret',
            'github',
            'fiesta_red'
        );

        self::assertEquals($generic_provider, $res);
    }

    public function testItUpdatesProvider(): void
    {
        $generic_provider_dao     = $this->createMock(GenericProviderDao::class);
        $generic_provider_manager = new GenericProviderManager(
            $generic_provider_dao
        );

        $provider = new GenericProvider(
            0,
            'Provider',
            'https://example.com/auth',
            'https://example.com/token',
            'https://example.com/jwks',
            'https://example.com/userinfo',
            'ID',
            'Secret',
            false,
            'github',
            'fiesta_red'
        );

        $generic_provider_dao->expects(self::once())->method('save');
        $generic_provider_manager->updateGenericProvider($provider);
    }

    public function testItChecksDataBeforeManipulatingGenericProvider(): void
    {
        $generic_provider_dao     = $this->createMock(GenericProviderDao::class);
        $generic_provider_manager = new GenericProviderManager(
            $generic_provider_dao
        );

        $generic_provider_dao->expects(self::never())->method('create');
        $generic_provider_dao->expects(self::never())->method('save');
        $this->expectException(ProviderMalformedDataException::class);

        $provider = new GenericProvider(
            0,
            'Provider',
            'Not A URL',
            'Not A URL',
            'Not A URL',
            'Not A URL',
            'ID',
            'Secret',
            false,
            'github',
            'fiesta_red'
        );

        $jwks_endpoint = $provider->getJWKSEndpoint();
        self::assertNotNull($jwks_endpoint);

        $generic_provider_manager->createGenericProvider(
            $provider->getName(),
            $provider->getAuthorizationEndpoint(),
            $provider->getTokenEndpoint(),
            $jwks_endpoint,
            $provider->getUserInfoEndpoint(),
            $provider->getClientId(),
            $provider->getClientSecret(),
            $provider->getIcon(),
            $provider->getColor()
        );
        $generic_provider_manager->updateGenericProvider($provider);
    }
}
