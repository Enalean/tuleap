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

namespace Tuleap\User\Password\Reset;

use Tuleap\Test\PHPUnit\TestIntegrationTestCase;

final class LostPasswordDAOTest extends TestIntegrationTestCase
{
    private const TEST_USER_ID = 102;

    /**
     * @var LostPasswordDAO
     */
    private $dao;

    protected function setUp(): void
    {
        $this->dao = new LostPasswordDAO();
    }

    public function testTokenCreationIsRateLimitedSoWeGetAnExceptionIfWeDontWaitEnoughTimeBetweenTwoCreationsForTheSameUser(): void
    {
        $token_id_1 = $this->dao->create(self::TEST_USER_ID, 'verifier', 10);
        $this->assertIsInt($token_id_1);

        $this->expectException(RecentlyCreatedCodeException::class);
        $this->dao->create(self::TEST_USER_ID, 'verifier', 11);
    }

    public function testTokenCreationIsRateLimitedAndAcceptsTheCreationIfWeWaitEnoughTimeBetweenTwoCreationsForTheSameUser(): void
    {
        $token_id_1 = $this->dao->create(self::TEST_USER_ID, 'verifier', 10);
        $this->assertIsInt($token_id_1);

        $token_id_2 = $this->dao->create(self::TEST_USER_ID, 'verifier', 999999);
        $this->assertIsInt($token_id_2);
    }
}
