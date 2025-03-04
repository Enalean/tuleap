<?php
/**
 * Copyright (c) Enalean, 2024-Present. All Rights Reserved.
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

namespace Tuleap\CrossTracker\Report\Query\Advanced\OrderByBuilder\Field\UserList;

use PFUser;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Test\Stubs\ProvideCurrentUserStub;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\OrderByDirection;
use UserHelper;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class UserOrderByBuilderTest extends TestCase
{
    public function testNoPreferences(): void
    {
        $user = $this->createMock(PFUser::class);
        $user->method('getPreference')->with(PFUser::PREFERENCE_NAME_DISPLAY_USERS)->willReturn(false);
        $builder = new UserOrderByBuilder(ProvideCurrentUserStub::buildWithUser($user));
        self::assertSame(
            "CONCAT(alias.realname, ' (', alias.user_name, ')') ASC",
            $builder->getOrderByForUsers('alias', OrderByDirection::ASCENDING),
        );
    }

    public function testPreferenceLogin(): void
    {
        $user = $this->createMock(PFUser::class);
        $user->method('getPreference')->with(PFUser::PREFERENCE_NAME_DISPLAY_USERS)->willReturn((string) UserHelper::PREFERENCES_LOGIN);
        $builder = new UserOrderByBuilder(ProvideCurrentUserStub::buildWithUser($user));
        self::assertSame(
            'CONCAT(alias.user_name) ASC',
            $builder->getOrderByForUsers('alias', OrderByDirection::ASCENDING),
        );
    }

    public function testPreferenceRealname(): void
    {
        $user = $this->createMock(PFUser::class);
        $user->method('getPreference')->with(PFUser::PREFERENCE_NAME_DISPLAY_USERS)->willReturn((string) UserHelper::PREFERENCES_REAL_NAME);
        $builder = new UserOrderByBuilder(ProvideCurrentUserStub::buildWithUser($user));
        self::assertSame(
            'CONCAT(alias.realname) ASC',
            $builder->getOrderByForUsers('alias', OrderByDirection::ASCENDING),
        );
    }

    public function testPreferenceLoginRealname(): void
    {
        $user = $this->createMock(PFUser::class);
        $user->method('getPreference')->with(PFUser::PREFERENCE_NAME_DISPLAY_USERS)->willReturn((string) UserHelper::PREFERENCES_LOGIN_AND_NAME);
        $builder = new UserOrderByBuilder(ProvideCurrentUserStub::buildWithUser($user));
        self::assertSame(
            "CONCAT(alias.user_name, ' (', alias.realname, ')') ASC",
            $builder->getOrderByForUsers('alias', OrderByDirection::ASCENDING),
        );
    }

    public function testPreferenceRealnameLogin(): void
    {
        $user = $this->createMock(PFUser::class);
        $user->method('getPreference')->with(PFUser::PREFERENCE_NAME_DISPLAY_USERS)->willReturn((string) UserHelper::PREFERENCES_NAME_AND_LOGIN);
        $builder = new UserOrderByBuilder(ProvideCurrentUserStub::buildWithUser($user));
        self::assertSame(
            "CONCAT(alias.realname, ' (', alias.user_name, ')') ASC",
            $builder->getOrderByForUsers('alias', OrderByDirection::ASCENDING),
        );
    }
}
