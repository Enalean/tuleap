<?php
/**
 * Copyright (c) Enalean, 2018-Present. All Rights Reserved.
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

use UserManager;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class CollectionOfUserInvolvedInNotificationPresenterBuilderTest extends \Tuleap\Test\PHPUnit\TestCase
{
    protected function tearDown(): void
    {
        \UserHelper::clearInstance();
    }

    public function testPresentersAreRetrievedSortedAlphabetically(): void
    {
        $users_to_notify_dao            = $this->createMock(UsersToNotifyDao::class);
        $unsubscribers_notification_dao = $this->createMock(UnsubscribersNotificationDAO::class);
        $user_rows                      = [
            ['user_id' => 200, 'user_name' => 'username1', 'realname' => 'Realname1'],
            ['user_id' => 102, 'user_name' => 'username2', 'realname' => 'Realname2'],
        ];
        $unsubscribers_notification_dao->method(
            'searchUsersUnsubcribedFromNotificationByTrackerID'
        )->willReturn($user_rows);

        $user1 = $this->createMock(\PFUser::class);
        $user1->method('getAvatarUrl');
        $user2 = $this->createMock(\PFUser::class);
        $user2->method('getAvatarUrl');
        $user_manager = $this->createMock(UserManager::class);
        $user_manager
            ->expects($this->exactly(2))
            ->method('getUserById')
            ->willReturnCallback(static fn (int $id) => match ($id) {
                200 => $user1,
                102 => $user2,
            });

        $builder = new CollectionOfUserInvolvedInNotificationPresenterBuilder(
            $users_to_notify_dao,
            $unsubscribers_notification_dao,
            $user_manager
        );

        $tracker = $this->createMock(\Tuleap\Tracker\Tracker::class);
        $tracker->method('getId')->willReturn(101);

        $user_helper = $this->createMock(\UserHelper::class);
        \UserHelper::setInstance($user_helper);
        $user1_display_name = 'username1 (Realname1)';
        $user2_display_name = 'username2 (Realname2)';
        $user_helper->method('getDisplayName')->willReturn($user2_display_name, $user1_display_name);

        $presenters = $builder->getCollectionOfNotificationUnsubscribersPresenter($tracker);

        $this->assertCount(2, $presenters);
        $this->assertEquals($user1_display_name, $presenters[0]->label);
        $this->assertEquals($user2_display_name, $presenters[1]->label);
    }
}
