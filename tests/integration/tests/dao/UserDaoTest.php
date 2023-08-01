<?php
/**
 * Copyright (c) Enalean, 2023-Present. All Rights Reserved.
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

namespace Tuleap\dao;

use Tuleap\Test\PHPUnit\TestCase;

final class UserDaoTest extends TestCase
{
    private \UserDao $dao;

    public function setUp(): void
    {
        $this->dao = new \UserDao();
    }

    public function testItSwitchThenRetrievePasswordlessOnly(): void
    {
        $user_id = $this->createUserWithName((string) time());
        self::assertFalse($this->dao->isPasswordlessOnlyAuth($user_id));

        $this->dao->switchPasswordlessOnlyAuth($user_id, true);
        self::assertTrue($this->dao->isPasswordlessOnlyAuth($user_id));

        $this->dao->switchPasswordlessOnlyAuth($user_id, false);
        self::assertFalse($this->dao->isPasswordlessOnlyAuth($user_id));
    }

    private function createUserWithName(string $name): int
    {
        return $this->dao->create(
            $name,
            null,
            null,
            null,
            null,
            null,
            null,
            null,
            null,
            null,
            null,
            null,
            null,
            null,
            null,
            null,
            null,
            null,
            null,
            null,
            null,
            null,
        );
    }
}
