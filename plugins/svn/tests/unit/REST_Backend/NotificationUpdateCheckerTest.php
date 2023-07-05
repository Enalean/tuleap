<?php
/**
 *  Copyright (c) Enalean, 2017 - present. All Rights Reserved.
 *
 *  This file is a part of Tuleap.
 *
 *  Tuleap is free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  Tuleap is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License
 *  along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

namespace Tuleap\SVN\REST\v1;

use PHPUnit\Framework\MockObject\MockObject;
use ProjectUGroup;
use Tuleap\GlobalLanguageMock;
use Tuleap\SVN\Admin\MailNotification;
use Tuleap\SVN\Admin\MailNotificationManager;
use Tuleap\SVN\Notifications\EmailsToBeNotifiedRetriever;
use Tuleap\SVN\Repository\Repository;
use Tuleap\Test\Builders\ProjectTestBuilder;

final class NotificationUpdateCheckerTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use GlobalLanguageMock;

    private \ProjectUGroup $user_group_project_member;
    private \ProjectUGroup $user_group_101;
    private MailNotificationManager&MockObject $mail_notification_manager;
    private \PFUser $user_103;
    private \PFUser $user_102;
    private EmailsToBeNotifiedRetriever&MockObject $emails_to_be_notified_retriever;
    private Repository&MockObject $repository;
    private NotificationUpdateChecker $notification_update_checker;

    protected function setUp(): void
    {
        parent::setUp();

        $this->mail_notification_manager       = $this->createMock(\Tuleap\SVN\Admin\MailNotificationManager::class);
        $this->emails_to_be_notified_retriever = $this->createMock(
            \Tuleap\SVN\Notifications\EmailsToBeNotifiedRetriever::class
        );

        $this->notification_update_checker = new NotificationUpdateChecker(
            $this->mail_notification_manager,
            $this->emails_to_be_notified_retriever
        );

        $this->user_102 = new \PFUser(['user_id' => 102]);
        $this->user_103 = new \PFUser(['user_id' => 103]);

        $this->user_group_101            = new ProjectUGroup(['ugroup_id' => 101]);
        $this->user_group_project_member = new ProjectUGroup(['ugroup_id' => ProjectUGroup::PROJECT_MEMBERS]);

        $this->repository = $this->createMock(\Tuleap\SVN\Repository\Repository::class);
        $project          = ProjectTestBuilder::aProject()->withId(101)->build();

        $this->repository->method('getProject')->willReturn($project);
    }

    public function testItReturnsTrueWhenAPathIsRemoved(): void
    {
        $new_notifications     = [];
        $all_old_notifications = [
            new MailNotification(
                1,
                $this->repository,
                "/tags",
                ["foo@example.com", "bar@example.com"],
                [],
                []
            ),
        ];

        $this->mail_notification_manager->method('getByRepository')->willReturn($all_old_notifications);

        self::assertTrue(
            $this->notification_update_checker->hasNotificationChanged($this->repository, $new_notifications)
        );
    }

    public function testItReturnsTrueWhenPathDoesNotExists(): void
    {
        $new_notifications     = [
            new MailNotification(
                1,
                $this->repository,
                "/tags",
                ["foo@example.com", "bar@example.com"],
                [],
                []
            ),
        ];
        $all_old_notifications = [];

        $this->mail_notification_manager->method('getByRepository')->willReturn($all_old_notifications);

        self::assertTrue(
            $this->notification_update_checker->hasNotificationChanged($this->repository, $new_notifications)
        );
    }

    public function testItReturnsTrueWhenAPathIsAdded(): void
    {
        $new_notifications = [
            new MailNotification(
                1,
                $this->repository,
                "/tags",
                ["foo@example.com", "bar@example.com"],
                [],
                []
            ),
            new MailNotification(
                1,
                $this->repository,
                "/trunk",
                ["foo@example.com"],
                [],
                []
            ),
        ];

        $all_old_notifications = [
            new MailNotification(
                1,
                $this->repository,
                "/tags",
                ["foo@example.com", "bar@example.com"],
                [],
                []
            ),
        ];

        $this->mail_notification_manager->method('getByRepository')->willReturn($all_old_notifications);

        self::assertTrue(
            $this->notification_update_checker->hasNotificationChanged($this->repository, $new_notifications)
        );
    }

    public function testItReturnsTrueWhenPathExistsAndNotificationsAreDifferent(): void
    {
        $new_notifications = [
            new MailNotification(
                1,
                $this->repository,
                "/tags",
                ["foo@example.com", "bar@example.com"],
                [],
                []
            ),
        ];

        $all_old_notifications = [
            new MailNotification(
                1,
                $this->repository,
                "/tags",
                ["foo@example.com"],
                [],
                []
            ),
        ];
        $old_notifications     = $all_old_notifications;

        $this->mail_notification_manager->method('getByRepository')->willReturn($all_old_notifications);
        $this->emails_to_be_notified_retriever->method('getNotificationsForPath')
            ->with($this->repository, "/tags")
            ->willReturn($old_notifications);
        $this->mail_notification_manager->method('isAnExistingPath')
            ->with($this->repository, 0, "/tags")
            ->willReturn(true);

        self::assertTrue(
            $this->notification_update_checker->hasNotificationChanged($this->repository, $new_notifications)
        );
    }

    public function testItReturnsFalseWhenPathAreIdenticalEvenIfNotificationsAreNotInSameOrder(): void
    {
        $new_notifications = [
            new MailNotification(
                1,
                $this->repository,
                "/tags",
                ["bar@example.com", "foo@example.com"],
                [],
                []
            ),
        ];

        $all_old_notifications = [
            new MailNotification(
                1,
                $this->repository,
                "/tags",
                ["foo@example.com", "bar@example.com"],
                [],
                []
            ),
        ];
        $old_notifications     = $all_old_notifications;

        $this->mail_notification_manager->method('getByRepository')->willReturn($all_old_notifications);
        $this->emails_to_be_notified_retriever->method('getNotificationsForPath')
            ->with($this->repository, "/tags")
            ->willReturn($old_notifications);
        $this->mail_notification_manager->method('isAnExistingPath')
            ->with($this->repository, 0, "/tags")
            ->willReturn(true);

        self::assertFalse(
            $this->notification_update_checker->hasNotificationChanged($this->repository, $new_notifications)
        );
    }

    public function testItReturnsTrueWhenAtLeastOneUserIsProvided(): void
    {
        $new_notifications = [
            new MailNotification(
                1,
                $this->repository,
                "/tags",
                [],
                [],
                [$this->user_103]
            ),
        ];

        $all_old_notifications = [
            new MailNotification(
                1,
                $this->repository,
                "/tags",
                [],
                [],
                []
            ),
        ];
        $old_notifications     = $all_old_notifications;

        $this->mail_notification_manager->method('getByRepository')->willReturn($all_old_notifications);
        $this->emails_to_be_notified_retriever->method('getNotificationsForPath')
            ->with($this->repository, "/tags")
            ->willReturn($old_notifications);
        $this->mail_notification_manager->method('isAnExistingPath')
            ->with($this->repository, 0, "/tags")
            ->willReturn(true);

        self::assertTrue(
            $this->notification_update_checker->hasNotificationChanged($this->repository, $new_notifications)
        );
    }

    public function testItReturnsTrueWhenUsersAreAdded(): void
    {
        $new_notifications = [
            new MailNotification(
                1,
                $this->repository,
                "/tags",
                [],
                [$this->user_102, $this->user_103],
                []
            ),
        ];

        $all_old_notifications = [
            new MailNotification(
                1,
                $this->repository,
                "/tags",
                [],
                [$this->user_102],
                []
            ),
        ];
        $old_notifications     = $all_old_notifications;

        $this->mail_notification_manager->method('getByRepository')->willReturn($all_old_notifications);
        $this->emails_to_be_notified_retriever->method('getNotificationsForPath')
            ->with($this->repository, "/tags")
            ->willReturn($old_notifications);
        $this->mail_notification_manager->method('isAnExistingPath')
            ->with($this->repository, 0, "/tags")
            ->willReturn(true);

        self::assertTrue(
            $this->notification_update_checker->hasNotificationChanged($this->repository, $new_notifications)
        );
    }

    public function testItReturnsFalseWhenUsersAreIdentical(): void
    {
        $new_notifications = [
            new MailNotification(
                1,
                $this->repository,
                "/tags",
                [],
                [$this->user_102, $this->user_103],
                []
            ),
        ];

        $all_old_notifications = [
            new MailNotification(
                1,
                $this->repository,
                "/tags",
                [],
                [$this->user_102, $this->user_103],
                []
            ),
        ];
        $old_notifications     = $all_old_notifications;

        $this->mail_notification_manager->method('getByRepository')->willReturn($all_old_notifications);
        $this->emails_to_be_notified_retriever->method('getNotificationsForPath')
            ->with($this->repository, "/tags")
            ->willReturn($old_notifications);
        $this->mail_notification_manager->method('isAnExistingPath')
            ->with($this->repository, 0, "/tags")
            ->willReturn(true);

        self::assertFalse(
            $this->notification_update_checker->hasNotificationChanged($this->repository, $new_notifications)
        );
    }

    public function testItReturnsTrueWhenUserGroupsAreAdded(): void
    {
        $new_notifications = [
            new MailNotification(
                1,
                $this->repository,
                "/tags",
                [],
                [],
                [$this->user_group_project_member, $this->user_group_101]
            ),
        ];

        $all_old_notifications = [
            new MailNotification(
                1,
                $this->repository,
                "/tags",
                [],
                [],
                [$this->user_group_project_member]
            ),
        ];
        $old_notifications     = $all_old_notifications;

        $this->mail_notification_manager->method('getByRepository')->willReturn($all_old_notifications);
        $this->emails_to_be_notified_retriever->method('getNotificationsForPath')->with($this->repository, "/tags")->willReturn(
            $old_notifications
        );
        $this->mail_notification_manager->method('isAnExistingPath')
            ->with($this->repository, 0, "/tags")
            ->willReturn(true);

        self::assertTrue(
            $this->notification_update_checker->hasNotificationChanged($this->repository, $new_notifications)
        );
    }

    public function testItReturnsFalseWhenUserGroupsAreIdentical(): void
    {
        $new_notifications = [
            new MailNotification(
                1,
                $this->repository,
                "/tags",
                [],
                [],
                [$this->user_group_project_member, $this->user_group_101]
            ),
        ];

        $all_old_notifications = [
            new MailNotification(
                1,
                $this->repository,
                "/tags",
                [],
                [],
                [$this->user_group_project_member, $this->user_group_101]
            ),
        ];
        $old_notifications     = $all_old_notifications;

        $this->mail_notification_manager->method('getByRepository')->willReturn($all_old_notifications);
        $this->emails_to_be_notified_retriever->method('getNotificationsForPath')
            ->with($this->repository, "/tags")
            ->willReturn($old_notifications);
        $this->mail_notification_manager->method('isAnExistingPath')
            ->with($this->repository, 0, "/tags")
            ->willReturn(true);

        self::assertFalse(
            $this->notification_update_checker->hasNotificationChanged($this->repository, $new_notifications)
        );
    }
}
