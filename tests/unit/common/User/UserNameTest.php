<?php
/**
 * Copyright (c) Enalean 2022 - Present. All Rights Reserved.
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

namespace Tuleap\User;

use Tuleap\Test\Builders\UserTestBuilder;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class UserNameTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private const string USER_NAME = 'aluscavage';

    public function testItAddsAtBeforeTuleapUserName(): void
    {
        $user = UserTestBuilder::aUser()
            ->withUserName(self::USER_NAME)
            ->build();

        $committer = UserName::fromUser($user);

        self::assertSame('@' . self::USER_NAME, $committer->getName());
    }

    public function testItBuildsFromUsernameString(): void
    {
        $committer = UserName::fromUsername(self::USER_NAME);

        self::assertSame(self::USER_NAME, $committer->getName());
    }
}
