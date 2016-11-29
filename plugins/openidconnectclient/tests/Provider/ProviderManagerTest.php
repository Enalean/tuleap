<?php

/**
 * Copyright (c) Enalean, 2016. All Rights Reserved.
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

require_once(__DIR__ . '/../bootstrap.php');

use Tuleap\OpenIDConnectClient\Provider\Provider;
use Tuleap\OpenIDConnectClient\Provider\ProviderManager;

class ProviderManagerTest extends TuleapTestCase {
    public function itCreatesNewProvider() {
        $provider_dao     = mock('Tuleap\OpenIDConnectClient\Provider\ProviderDao');
        $provider_manager = new ProviderManager($provider_dao);

        $provider_dao->expectOnce('create');

        $provider_manager->create(
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

    public function itCreatesNewProviderWithAnEmptyUserInfoEndpoint()
    {
        $provider_dao     = mock('Tuleap\OpenIDConnectClient\Provider\ProviderDao');
        $provider_manager = new ProviderManager($provider_dao);

        $provider_dao->expectOnce('create');

        $provider_manager->create(
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

    public function itUpdatesProvider() {
        $provider_dao     = mock('Tuleap\OpenIDConnectClient\Provider\ProviderDao');
        $provider_dao->setReturnValue('save', true);
        $provider_manager = new ProviderManager($provider_dao);
        $provider         = new Provider(
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

        $provider_dao->expectOnce('save');

        $provider_manager->update($provider);
    }

    public function itChecksDataBeforeManipulatingAProvider() {
        $provider_dao     = mock('Tuleap\OpenIDConnectClient\Provider\ProviderDao');
        $provider_manager = new ProviderManager($provider_dao);

        $provider_dao->expectNever('create');
        $provider_dao->expectNever('save');
        $this->expectException('Tuleap\OpenIDConnectClient\Provider\ProviderMalformedDataException');

        $provider         = new Provider(
            0,
            'Provider',
            'Not A URL',
            'Not A URL',
            'Not A URL',
            'ID',
            'Secret',
            'github',
            false,
            'fiesta_red'
        );

        $provider_manager->create(
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
