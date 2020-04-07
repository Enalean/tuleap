<?php
/**
 * Copyright (c) Enalean, 2018-2019. All Rights Reserved.
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

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use UserManager;

require_once __DIR__ . '/../../bootstrap.php';

class CollectionOfUserInvolvedInNotificationPresenterBuilderTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    protected function tearDown(): void
    {
        \UserHelper::clearInstance();
    }

    public function testPresentersAreRetrievedSortedAlphabetically()
    {
        $users_to_notify_dao            = \Mockery::mock(UsersToNotifyDao::class);
        $unsubscribers_notification_dao = \Mockery::mock(UnsubscribersNotificationDAO::class);
        $user_rows                      = [
            ['user_id' => 200, 'user_name' => 'username1', 'realname' => 'Realname1', 'has_avatar' => 0],
            ['user_id' => 102, 'user_name' => 'username2', 'realname' => 'Realname2', 'has_avatar' => 0]
        ];
        $unsubscribers_notification_dao->shouldReceive(
            'searchUsersUnsubcribedFromNotificationByTrackerID'
        )->andReturns($user_rows);

        $user1 = \Mockery::mock(\PFUser::class);
        $user1->shouldReceive('getAvatarUrl');
        $user2 = \Mockery::mock(\PFUser::class);
        $user2->shouldReceive('getAvatarUrl');
        $user_manager = \Mockery::mock(UserManager::class);
        $user_manager
            ->shouldReceive('getUserById')
            ->with(200)
            ->once()
            ->andReturn($user1);
        $user_manager
            ->shouldReceive('getUserById')
            ->with(102)
            ->once()
            ->andReturn($user2);

        $builder    = new CollectionOfUserInvolvedInNotificationPresenterBuilder(
            $users_to_notify_dao,
            $unsubscribers_notification_dao,
            $user_manager
        );

        $tracker    = \Mockery::mock(\Tracker::class);
        $tracker->shouldReceive('getId')->andReturns(101);

        $user_helper = \Mockery::mock(\UserHelper::class);
        \UserHelper::setInstance($user_helper);
        $user1_display_name = 'username1 (Realname1)';
        $user2_display_name = 'username2 (Realname2)';
        $user_helper->shouldReceive('getDisplayName')->andReturn($user2_display_name, $user1_display_name);

        $presenters = $builder->getCollectionOfNotificationUnsubscribersPresenter($tracker);

        $this->assertCount(2, $presenters);
        $this->assertEquals($user1_display_name, $presenters[0]->label);
        $this->assertEquals($user2_display_name, $presenters[1]->label);
    }
}
