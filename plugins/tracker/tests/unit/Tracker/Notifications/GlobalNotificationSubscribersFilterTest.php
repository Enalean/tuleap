<?php
/**
 * Copyright (c) Enalean, 2018. All Rights Reserved.
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

require_once __DIR__ . '/../../bootstrap.php';

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Tracker;

class GlobalNotificationSubscribersFilterTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    public function testUnsubscribedUsersAreFiltered()
    {
        $unsubscriber_notification_dao = \Mockery::mock(UnsubscribersNotificationDAO::class);
        $unsubscriber_notification_dao->shouldReceive('searchUserIDHavingUnsubcribedFromNotificationByTrackerID')
            ->andReturn([101, 102]);

        $tracker = \Mockery::mock(Tracker::class);
        $tracker->shouldReceive('getId');

        $subscribers_filter = new GlobalNotificationSubscribersFilter($unsubscriber_notification_dao);
        $filtered_user_ids  = $subscribers_filter->filterInvalidUserIDs($tracker, ['105', '102']);

        $this->assertEquals(['105'], $filtered_user_ids);
    }
}
