<?php
/**
 * Copyright (c) Enalean, 2018 - Present. All Rights Reserved.
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

namespace Tuleap\Tracker\Notifications;

use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class GlobalNotificationSubscribersFilterTest extends \Tuleap\Test\PHPUnit\TestCase
{
    public function testUnsubscribedUsersAreFiltered(): void
    {
        $unsubscriber_notification_dao = $this->createMock(UnsubscribersNotificationDAO::class);
        $unsubscriber_notification_dao->method('searchUserIDHavingUnsubcribedFromNotificationByTrackerID')
            ->willReturn([101, 102]);

        $tracker = TrackerTestBuilder::aTracker()->build();

        $subscribers_filter = new GlobalNotificationSubscribersFilter($unsubscriber_notification_dao);
        $filtered_user_ids  = $subscribers_filter->filterInvalidUserIDs($tracker, ['105', '102']);

        $this->assertEquals(['105'], $filtered_user_ids);
    }
}
