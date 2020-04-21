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

namespace Tuleap\OAuth2Server\App;

use Mockery as M;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Tuleap\Authentication\SplitToken\SplitTokenVerificationString;
use Tuleap\Authentication\SplitToken\SplitTokenVerificationStringHasher;

final class ClientSecretUpdaterTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var ClientSecretUpdater
     */
    private $updater;
    /**
     * @var SplitTokenVerificationStringHasher
     */
    private $hasher;
    /**
     * @var M\LegacyMockInterface|M\MockInterface|AppDao
     */
    private $app_dao;
    /**
     * @var M\LegacyMockInterface|M\MockInterface|LastGeneratedClientSecretStore
     */
    private $client_secret_store;

    protected function setUp(): void
    {
        $this->hasher              = new SplitTokenVerificationStringHasher();
        $this->app_dao             = M::mock(AppDao::class);
        $this->client_secret_store = M::mock(LastGeneratedClientSecretStore::class);
        $this->updater             = new ClientSecretUpdater(
            $this->hasher,
            $this->app_dao,
            $this->client_secret_store
        );
    }

    public function testGeneratesClientSecretAndStoresItInAppAndStore(): void
    {
        $this->app_dao->shouldReceive('updateSecret')
            ->once()
            ->with(45, M::type("string"));
        $this->client_secret_store->shouldReceive('storeLastGeneratedClientSecret')
            ->once()
            ->with(45, M::type(SplitTokenVerificationString::class));
        $this->updater->updateClientSecret(45);
    }
}
