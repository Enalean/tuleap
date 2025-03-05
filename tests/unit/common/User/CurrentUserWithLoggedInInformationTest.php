<?php
/**
 * Copyright (c) Enalean, 2022-Present. All Rights Reserved.
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
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Test\Stubs\AnonymousUserTestProvider;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class CurrentUserWithLoggedInInformationTest extends TestCase
{
    public function testCreatesCurrentUserFromALoggedInUser(): void
    {
        $user         = UserTestBuilder::anActiveUser()->build();
        $current_user = CurrentUserWithLoggedInInformation::fromLoggedInUser($user);

        self::assertSame($user, $current_user->user);
        self::assertTrue($current_user->is_logged_in);
    }

    public function testCreatesCurrentUserFromAnAnonymousUser(): void
    {
        $current_user = CurrentUserWithLoggedInInformation::fromAnonymous(new AnonymousUserTestProvider());

        self::assertTrue($current_user->user->isAnonymous());
        self::assertFalse($current_user->is_logged_in);
    }

    public function testCannotConsiderAnAnonymousUserAsLoggedIn(): void
    {
        $this->expectException(\LogicException::class);
        CurrentUserWithLoggedInInformation::fromLoggedInUser(UserTestBuilder::anAnonymousUser()->build());
    }
}
