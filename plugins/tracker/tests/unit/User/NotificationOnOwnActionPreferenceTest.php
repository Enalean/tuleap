<?php
/*
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
 *
 */

declare(strict_types=1);

namespace Tuleap\Tracker\User;

use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;

final class NotificationOnOwnActionPreferenceTest extends TestCase
{
    public function testNoPreferenceMeansNotificationsAreReceived(): void
    {
        $user = UserTestBuilder::anActiveUser()->build();

        self::assertTrue(NotificationOnOwnActionPreference::userWantsNotification($user));
    }

    public function testPreferenceSetToYesMeansNotificationsAreReceived(): void
    {
        $user = UserTestBuilder::anActiveUser()->build();

        NotificationOnOwnActionPreference::updateUserWantsNotifications($user);

        self::assertTrue(NotificationOnOwnActionPreference::userWantsNotification($user));
    }

    public function testPreferenceSetToNoMeansNotificationsAreStopped(): void
    {
        $user = UserTestBuilder::anActiveUser()->build();

        NotificationOnOwnActionPreference::updateUserDoesNotWantNotification($user);

        self::assertFalse(NotificationOnOwnActionPreference::userWantsNotification($user));
    }
}
