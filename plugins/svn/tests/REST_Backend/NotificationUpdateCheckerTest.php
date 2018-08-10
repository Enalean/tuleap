<?php
/**
 *  Copyright (c) Enalean, 2017-2018. All Rights Reserved.
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

use ProjectUGroup;
use Tuleap\Svn\Admin\MailNotification;
use Tuleap\Svn\Admin\MailNotificationManager;
use Tuleap\Svn\Notifications\EmailsToBeNotifiedRetriever;
use Tuleap\Svn\Repository\Repository;
use TuleapTestCase;

require_once __DIR__ . '/../bootstrap.php';

class NotificationUpdateCheckerTest extends TuleapTestCase
{
    /**
     * @var \ProjectUGroup
     */
    private $user_group_project_member;
    /**
     * @var \ProjectUGroup
     */
    private $user_group_101;
    /**
     * @var MailNotificationManager
     */
    private $mail_notification_manager;
    /**
     * @var \PFUser
     */
    private $user_103;
    /**
     * @var \PFUser
     */
    private $user_102;
    /**
     * @var EmailsToBeNotifiedRetriever
     */
    private $emails_to_be_notified_retriever;
    /**
     * @var Repository
     */
    private $repository;

    /**
     * @var NotificationUpdateChecker
     */
    private $notification_update_checker;

    public function setUp()
    {
        parent::setUp();

        $this->mail_notification_manager       = mock('Tuleap\Svn\Admin\MailNotificationManager');
        $this->emails_to_be_notified_retriever = mock('Tuleap\Svn\Notifications\EmailsToBeNotifiedRetriever');

        $this->notification_update_checker = new NotificationUpdateChecker(
            $this->mail_notification_manager,
            $this->emails_to_be_notified_retriever
        );

        $this->user_102 = aUser()->withId(102)->withLang('en_US')->build();
        $this->user_103 = aUser()->withId(103)->withLang('en_US')->build();

        $this->user_group_101 = mock('ProjectUGroup');
        stub($this->user_group_101)->getId()->returns(101);
        $this->user_group_project_member = mock('ProjectUGroup');
        stub($this->user_group_project_member)->getId()->returns(ProjectUGroup::PROJECT_MEMBERS);

        $this->repository = mock('Tuleap\Svn\Repository\Repository');
        stub($this->repository)->getProject()->returns(aMockProject()->withId(101)->build());
    }

    public function itReturnsTrueWhenAPathIsRemoved()
    {
        $new_notifications = array();
        $all_old_notifications = array(
            new MailNotification(
                1,
                $this->repository,
                "/tags",
                array("foo@example.com", "bar@example.com"),
                array(),
                array()
            )
        );

        stub($this->mail_notification_manager)->getByRepository()->returnsDar($all_old_notifications);

        $this->assertTrue(
            $this->notification_update_checker->hasNotificationChanged($this->repository, $new_notifications)
        );
    }

    public function itReturnsTrueWhenPathDoesNotExists()
    {
        $new_notifications = array(
            new MailNotification(
                1,
                $this->repository,
                "/tags",
                array("foo@example.com", "bar@example.com"),
                array(),
                array()
            )
        );
        $all_old_notifications = [];

        stub($this->mail_notification_manager)->getByRepository()->returns($all_old_notifications);

        $this->assertTrue(
            $this->notification_update_checker->hasNotificationChanged($this->repository, $new_notifications)
        );
    }

    public function itReturnsTrueWhenAPathIsAdded()
    {
        $new_notifications = array(
            new MailNotification(
                1,
                $this->repository,
                "/tags",
                array("foo@example.com", "bar@example.com"),
                array(),
                array()
            ),
            new MailNotification(
                1,
                $this->repository,
                "/trunk",
                array("foo@example.com"),
                array(),
                array()
            )
        );

        $all_old_notifications = array(
            new MailNotification(
                1,
                $this->repository,
                "/tags",
                array("foo@example.com", "bar@example.com"),
                array(),
                array()
            )
        );

        stub($this->mail_notification_manager)->getByRepository()->returns($all_old_notifications);

        $this->assertTrue(
            $this->notification_update_checker->hasNotificationChanged($this->repository, $new_notifications)
        );
    }

    public function itReturnsTrueWhenPathExistsAndNotificationsAreDifferent()
    {
        $new_notifications = array(
            new MailNotification(
                1,
                $this->repository,
                "/tags",
                array("foo@example.com", "bar@example.com"),
                array(),
                array()
            )
        );

        $all_old_notifications = array(
            new MailNotification(
                1,
                $this->repository,
                "/tags",
                array("foo@example.com"),
                array(),
                array()
            )
        );
        $old_notifications = $all_old_notifications;

        stub($this->mail_notification_manager)->getByRepository()->returns($all_old_notifications);
        stub($this->emails_to_be_notified_retriever)->getNotificationsForPath($this->repository, "/tags")->returns($old_notifications);
        stub($this->mail_notification_manager)->isAnExistingPath($this->repository, 0, "/tags")->returns(true);

        $this->assertTrue(
            $this->notification_update_checker->hasNotificationChanged($this->repository, $new_notifications)
        );
    }

    public function itReturnsFalseWhenPathAreIdenticalEvenIfNotificationsAreNotInSameOrder()
    {
        $new_notifications = array(
            new MailNotification(
                1,
                $this->repository,
                "/tags",
                array("bar@example.com", "foo@example.com"),
                array(),
                array()
            )
        );

        $all_old_notifications = array(
            new MailNotification(
                1,
                $this->repository,
                "/tags",
                array("foo@example.com", "bar@example.com"),
                array(),
                array()
            )
        );
        $old_notifications = $all_old_notifications;

        stub($this->mail_notification_manager)->getByRepository()->returns($all_old_notifications);
        stub($this->emails_to_be_notified_retriever)->getNotificationsForPath($this->repository, "/tags")->returns($old_notifications);
        stub($this->mail_notification_manager)->isAnExistingPath($this->repository, 0, "/tags")->returns(true);

        $this->assertFalse(
            $this->notification_update_checker->hasNotificationChanged($this->repository, $new_notifications)
        );
    }

    public function itReturnsTrueWhenAtLeastOneUserIsProvided()
    {
        $new_notifications = array(
            new MailNotification(
                1,
                $this->repository,
                "/tags",
                array(),
                array(),
                array($this->user_103)
            )
        );

        $all_old_notifications = array(
            new MailNotification(
                1,
                $this->repository,
                "/tags",
                array(),
                array(),
                array()
            )
        );
        $old_notifications = $all_old_notifications;

        stub($this->mail_notification_manager)->getByRepository()->returns($all_old_notifications);
        stub($this->emails_to_be_notified_retriever)->getNotificationsForPath($this->repository, "/tags")->returns($old_notifications);
        stub($this->mail_notification_manager)->isAnExistingPath($this->repository, 0, "/tags")->returns(true);

        $this->assertTrue(
            $this->notification_update_checker->hasNotificationChanged($this->repository, $new_notifications)
        );
    }

    public function itReturnsTrueWhenUsersAreAdded()
    {
        $new_notifications = array(
            new MailNotification(
                1,
                $this->repository,
                "/tags",
                array(),
                array($this->user_102, $this->user_103),
                array()
            )
        );

        $all_old_notifications = array(
            new MailNotification(
                1,
                $this->repository,
                "/tags",
                array(),
                array($this->user_102),
                array()
            )
        );
        $old_notifications = $all_old_notifications;

        stub($this->mail_notification_manager)->getByRepository()->returns($all_old_notifications);
        stub($this->emails_to_be_notified_retriever)->getNotificationsForPath($this->repository, "/tags")->returns($old_notifications);
        stub($this->mail_notification_manager)->isAnExistingPath($this->repository, 0, "/tags")->returns(true);

        $this->assertTrue(
            $this->notification_update_checker->hasNotificationChanged($this->repository, $new_notifications)
        );
    }

    public function itReturnsFalseWhenUsersAreIdentical()
    {
        $new_notifications = array(
            new MailNotification(
                1,
                $this->repository,
                "/tags",
                array(),
                array($this->user_102, $this->user_103),
                array()
            )
        );

        $all_old_notifications = array(
            new MailNotification(
                1,
                $this->repository,
                "/tags",
                array(),
                array($this->user_102, $this->user_103),
                array()
            )
        );
        $old_notifications = $all_old_notifications;

        stub($this->mail_notification_manager)->getByRepository()->returns($all_old_notifications);
        stub($this->emails_to_be_notified_retriever)->getNotificationsForPath($this->repository, "/tags")->returns($old_notifications);
        stub($this->mail_notification_manager)->isAnExistingPath($this->repository, 0, "/tags")->returns(true);

        $this->assertFalse(
            $this->notification_update_checker->hasNotificationChanged($this->repository, $new_notifications)
        );
    }

    public function itReturnsTrueWhenUserGroupsAreAdded()
    {
        $new_notifications = array(
            new MailNotification(
                1,
                $this->repository,
                "/tags",
                array(),
                array(),
                array($this->user_group_project_member, $this->user_group_101)
            )
        );

        $all_old_notifications = array(
            new MailNotification(
                1,
                $this->repository,
                "/tags",
                array(),
                array(),
                array($this->user_group_project_member)
            )
        );
        $old_notifications = $all_old_notifications;

        stub($this->mail_notification_manager)->getByRepository()->returns($all_old_notifications);
        stub($this->emails_to_be_notified_retriever)->getNotificationsForPath($this->repository, "/tags")->returns($old_notifications);
        stub($this->mail_notification_manager)->isAnExistingPath($this->repository, 0, "/tags")->returns(true);

        $this->assertTrue(
            $this->notification_update_checker->hasNotificationChanged($this->repository, $new_notifications)
        );
    }

    public function itReturnsFalseWhenUserGroupsAreIdentical()
    {
        $new_notifications = array(
            new MailNotification(
                1,
                $this->repository,
                "/tags",
                array(),
                array(),
                array($this->user_group_project_member, $this->user_group_101)
            )
        );

        $all_old_notifications = array(
            new MailNotification(
                1,
                $this->repository,
                "/tags",
                array(),
                array(),
                array($this->user_group_project_member, $this->user_group_101)
            )
        );
        $old_notifications = $all_old_notifications;

        stub($this->mail_notification_manager)->getByRepository()->returns($all_old_notifications);
        stub($this->emails_to_be_notified_retriever)->getNotificationsForPath($this->repository, "/tags")->returns($old_notifications);
        stub($this->mail_notification_manager)->isAnExistingPath($this->repository, 0, "/tags")->returns(true);

        $this->assertFalse(
            $this->notification_update_checker->hasNotificationChanged($this->repository, $new_notifications)
        );
    }
}
