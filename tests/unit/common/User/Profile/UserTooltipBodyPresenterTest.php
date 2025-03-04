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

namespace Tuleap\User\Profile;

use Tuleap\Test\Builders\UserTestBuilder;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class UserTooltipBodyPresenterTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private const USER_ID = 132;
    private const EMAIL   = 'goosish@example.com';

    public function testItBuildsFromUser(): void
    {
        $user = UserTestBuilder::aUser()
            ->withId(self::USER_ID)
            ->withEmail(self::EMAIL)
            ->build();

        $presenter = UserTooltipBodyPresenter::fromUser($user);

        self::assertSame(self::USER_ID, $presenter->user_id);
        self::assertSame(self::EMAIL, $presenter->email);
    }
}
