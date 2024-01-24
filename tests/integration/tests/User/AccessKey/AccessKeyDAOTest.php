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

namespace Tuleap\User\AccessKey;

use Tuleap\DB\DBFactory;
use Tuleap\Test\PHPUnit\TestIntegrationTestCase;
use Tuleap\User\AccessKey\Scope\AccessKeyScopeDAO;

final class AccessKeyDAOTest extends TestIntegrationTestCase
{
    /**
     * @var AccessKeyDAO
     */
    private $access_key_dao;
    /**
     * @var AccessKeyScopeDAO
     */
    private $access_key_scope_dao;

    public function setUp(): void
    {
        $this->access_key_dao       = new AccessKeyDAO();
        $this->access_key_scope_dao = new AccessKeyScopeDAO();
    }

    public function testKeyAndAssociatedScopesAreRemoved(): void
    {
        $user_id = 101;

        $key_id = $this->createAccessKey($user_id, 10, null, 'scope:a', 'scope:b');

        $this->access_key_dao->deleteByUserIDAndKeyIDs($user_id, [$key_id]);

        $this->assertNull($this->access_key_dao->searchAccessKeyVerificationAndTraceabilityDataByID($key_id));
        $this->assertEmpty($this->access_key_scope_dao->searchScopeKeysByAccessKeyID($key_id));
    }

    public function testExpiredKeyWithAssociatedScopesAreRemoved(): void
    {
        $current_time = 10;

        $key_id = $this->createAccessKey(101, $current_time, 5, 'scope:a', 'scope:b');

        $this->access_key_dao->deleteByExpirationDate($current_time);

        $this->assertNull($this->access_key_dao->searchAccessKeyVerificationAndTraceabilityDataByID($key_id));
        $this->assertEmpty($this->access_key_scope_dao->searchScopeKeysByAccessKeyID($key_id));
    }

    public function testKeysWithNoScopesAreRemoved(): void
    {
        $key_id = $this->createAccessKey(101, 10, null, 'scope:a');

        DBFactory::getMainTuleapDBConnection()->getDB()->run('DELETE FROM user_access_key_scope');

        $this->access_key_dao->deleteKeysWithNoScopes();

        $this->assertNull($this->access_key_dao->searchAccessKeyVerificationAndTraceabilityDataByID($key_id));
    }

    private function createAccessKey(int $user_id, int $current_time, ?int $expiration, string ...$scope_keys): int
    {
        $key_id = $this->access_key_dao->create($user_id, 'hash_verification_string', $current_time, 'description', $expiration);
        $this->access_key_scope_dao->saveScopeKeysByAccessKeyID($key_id, ...$scope_keys);

        return $key_id;
    }
}
