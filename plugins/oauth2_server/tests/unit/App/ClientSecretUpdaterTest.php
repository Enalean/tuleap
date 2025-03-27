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

use Tuleap\Authentication\SplitToken\SplitTokenVerificationStringHasher;
use Tuleap\OAuth2ServerCore\App\AppDao;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class ClientSecretUpdaterTest extends \Tuleap\Test\PHPUnit\TestCase
{
    /**
     * @var ClientSecretUpdater
     */
    private $updater;
    /**
     * @var SplitTokenVerificationStringHasher
     */
    private $hasher;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&AppDao
     */
    private $app_dao;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&LastGeneratedClientSecretStore
     */
    private $client_secret_store;

    protected function setUp(): void
    {
        $this->hasher              = new SplitTokenVerificationStringHasher();
        $this->app_dao             = $this->createMock(AppDao::class);
        $this->client_secret_store = $this->createMock(LastGeneratedClientSecretStore::class);
        $this->updater             = new ClientSecretUpdater(
            $this->hasher,
            $this->app_dao,
            $this->client_secret_store
        );
    }

    public function testGeneratesClientSecretAndStoresItInAppAndStore(): void
    {
        $this->app_dao->expects($this->once())->method('updateSecret')
            ->with(45, self::anything());
        $this->client_secret_store->expects($this->once())->method('storeLastGeneratedClientSecret')
            ->with(45, self::anything());
        $this->updater->updateClientSecret(45);
    }
}
