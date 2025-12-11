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

use Override;
use PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Test\Stubs\User\Avatar\ProvideUserAvatarUrlStub;
use Tuleap\Tracker\Tracker;
use UserHelper;
use UserManager;

#[DisableReturnValueGenerationForTestDoubles]
final class CollectionOfUserInvolvedInNotificationPresenterBuilderTest extends TestCase
{
    #[Override]
    protected function tearDown(): void
    {
        UserHelper::clearInstance();
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

        $user1        = UserTestBuilder::anActiveUser()->withId(102)->withUserName('username1')->withRealName(
            'Realname1'
        )->withAvatarUrl('https://example.com/users/username2/avatar-51fgsg.png')->build();
        $user2        = UserTestBuilder::anActiveUser()->withId(200)->withUserName('username2')->withRealName(
            'Realname2'
        )->withAvatarUrl('https://example.com/users/username2/avatar-154dfgdg5gbdb.png')->build();
        $user_manager = $this->createMock(UserManager::class);
        $user_manager
            ->expects($this->exactly(2))
            ->method('getUserById')
            ->willReturnCallback(static fn (int $id) => match ($id) {
                200 => $user1,
                102 => $user2,
            });

        $tracker = $this->createMock(Tracker::class);
        $tracker->method('getId')->willReturn(101);

        $user_helper = $this->createMock(UserHelper::class);
        UserHelper::setInstance($user_helper);
        $user1_display_name = 'username1 (Realname1)';
        $user2_display_name = 'username2 (Realname2)';
        $user_helper->method('getDisplayName')->willReturn($user2_display_name, $user1_display_name);
        $user_helper->method('getUserUrl')->willReturn('/users/' . urlencode($user1->getUserName()), '/users/' . urlencode($user2->getUserName()));

        $builder    = new CollectionOfUserInvolvedInNotificationPresenterBuilder(
            $users_to_notify_dao,
            $unsubscribers_notification_dao,
            $user_manager,
            $user_helper,
            ProvideUserAvatarUrlStub::build()
        );
        $presenters = $builder->getCollectionOfNotificationUnsubscribersPresenter($tracker);

        $this->assertCount(2, $presenters);
        $this->assertEquals($user1_display_name, $presenters[0]->display_name);
        $this->assertEquals($user2_display_name, $presenters[1]->display_name);
    }
}
