<?php
/**
 *  Copyright (c) Enalean, 2017. All Rights Reserved.
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

use Tuleap\Svn\Admin\MailNotification;
use Tuleap\Svn\Admin\MailNotificationDao;
use Tuleap\Svn\Admin\MailNotificationManager;
use Tuleap\Svn\Notifications\EmailsToBeNotifiedRetriever;
use Tuleap\Svn\Notifications\Notification;
use Tuleap\Svn\Notifications\NotificationsEmailsBuilder;
use Tuleap\Svn\Notifications\UgroupsToNotifyDao;
use Tuleap\Svn\Notifications\UsersToNotifyDao;
use Tuleap\Svn\Repository\Repository;
use TuleapTestCase;

require_once __DIR__ . '/../bootstrap.php';

class NotificationUpdateCheckerTest extends TuleapTestCase
{
    /**
     * @var \UserManager
     */
    private $user_manager;
    /**
     * @var UsersToNotifyDao
     */
    private $user_to_notify_dao;
    /**
     * @var UgroupsToNotifyDao
     */
    private $ugroup_to_notify_dao;
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

    /**
     * @var MailNotificationDao
     */
    private $mail_notification_dao;

    public function setUp()
    {
        parent::setUp();

        $this->mail_notification_dao = mock('Tuleap\Svn\Admin\MailNotificationDao');
        $this->user_to_notify_dao    = mock('Tuleap\Svn\Notifications\UsersToNotifyDao');
        $this->ugroup_to_notify_dao  = mock('Tuleap\Svn\Notifications\UgroupsToNotifyDao');
        $mail_notification_manager   = new MailNotificationManager(
            $this->mail_notification_dao,
            $this->user_to_notify_dao,
            $this->ugroup_to_notify_dao,
            mock('\ProjectHistoryDao'),
            new NotificationsEmailsBuilder()
        );

        $this->user_manager                    = mock('\UserManager');
        $this->emails_to_be_notified_retriever = new EmailsToBeNotifiedRetriever(
            $mail_notification_manager,
            $this->user_to_notify_dao,
            $this->ugroup_to_notify_dao,
            mock('\UGroupManager'),
            $this->user_manager
        );

        $this->notification_update_checker = new NotificationUpdateChecker(
            $mail_notification_manager,
            $this->emails_to_be_notified_retriever
        );

        $this->user_102 = aUser()->withId(102)->build();
        $this->user_103 = aUser()->withId(103)->build();

        $this->repository = mock('Tuleap\Svn\Repository\Repository');
        stub($this->repository)->getProject()->returns(aMockProject()->withId(101)->build());
    }

    public function itReturnsTrueWhenAPathIsRemoved()
    {
        $new_notifications = array();

        $old_notifications = array(
            'id'           => 1,
            'mailing_list' => "foo@example.com,bar@example.com,test@example.com",
            'svn_path'     => "/tags"
        );

        stub($this->mail_notification_dao)->searchByRepositoryId()->returnsDar($old_notifications);
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

        $old_notifications = null;

        stub($this->mail_notification_dao)->searchByRepositoryId()->returnsDar($old_notifications);
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

        $old_notifications = array(
            'id'           => 1,
            'mailing_list' => "foo@example.com,bar@example.com,test@example.com",
            'svn_path'     => "/tags"
        );

        stub($this->mail_notification_dao)->searchByRepositoryId()->returnsDar($old_notifications);
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

        $old_notifications = array(
            'id'           => 1,
            'mailing_list' => "foo@example.com,bar@example.com,test@example.com",
            'svn_path'     => "/tags"
        );

        stub($this->mail_notification_dao)->searchByRepositoryId()->returnsDar($old_notifications);
        stub($this->mail_notification_dao)->searchByPathStrictlyEqual()->returnsDar($old_notifications);
        stub($this->mail_notification_dao)->searchByPath()->returnsDar($old_notifications);
        stub($this->ugroup_to_notify_dao)->searchUgroupsByNotificationId()->returnsDar(null);
        stub($this->user_to_notify_dao)->searchUsersByNotificationId()->returnsDar(null);

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

        $old_notifications = array(
            'id'           => 1,
            'mailing_list' => "foo@example.com,bar@example.com",
            'svn_path'     => "/tags"
        );

        stub($this->mail_notification_dao)->searchByRepositoryId()->returnsDar($old_notifications);
        stub($this->mail_notification_dao)->searchByPathStrictlyEqual()->returnsDar($old_notifications);
        stub($this->mail_notification_dao)->searchByPath()->returnsDar($old_notifications);
        stub($this->ugroup_to_notify_dao)->searchUgroupsByNotificationId()->returnsDar(null);
        stub($this->user_to_notify_dao)->searchUsersByNotificationId()->returnsDar(null);

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

        $old_notifications = array(
            'id'           => 1,
            'mailing_list' => "",
            'svn_path'     => "/tags"
        );

        stub($this->mail_notification_dao)->searchByRepositoryId()->returnsDar($old_notifications);
        stub($this->mail_notification_dao)->searchByPathStrictlyEqual()->returnsDar($old_notifications);
        stub($this->mail_notification_dao)->searchByPath()->returnsDar($old_notifications);
        stub($this->ugroup_to_notify_dao)->searchUgroupsByNotificationId()->returnsDar(null);
        stub($this->user_to_notify_dao)->searchUsersByNotificationId()->returnsDar(array("user_id" => 103));
        stub($this->user_manager)->getUserById(103)->returns($this->user_102, $this->user_103);

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
                array(),
                array($this->user_102, $this->user_103)
            )
        );

        $old_notifications = array(
            'id'           => 1,
            'mailing_list' => "",
            'svn_path'     => "/tags"
        );

        stub($this->mail_notification_dao)->searchByRepositoryId()->returnsDar($old_notifications);
        stub($this->mail_notification_dao)->searchByPathStrictlyEqual()->returnsDar($old_notifications);
        stub($this->mail_notification_dao)->searchByPath()->returnsDar($old_notifications);
        stub($this->ugroup_to_notify_dao)->searchUgroupsByNotificationId()->returnsDar(null);
        stub($this->user_to_notify_dao)->searchUsersByNotificationId()->returnsDar(array("user_id" => 103));
        stub($this->user_manager)->getUserById(103)->returns($this->user_103);

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
                array($this->user_103, $this->user_102),
                array()
            )
        );

        $old_notifications = array(
            'id'           => 1,
            'mailing_list' => "",
            'svn_path'     => "/tags"
        );

        stub($this->mail_notification_dao)->searchByRepositoryId()->returnsDar($old_notifications);
        stub($this->mail_notification_dao)->searchByPathStrictlyEqual()->returnsDar($old_notifications);
        stub($this->mail_notification_dao)->searchByPath()->returnsDar($old_notifications);
        stub($this->ugroup_to_notify_dao)->searchUgroupsByNotificationId()->returnsDar(null);
        stub($this->user_to_notify_dao)->searchUsersByNotificationId()->returnsDar(
            array("user_id" => 103),
            array("user_id" => 102)
        );
        stub($this->user_manager)->getUserById(103)->returns($this->user_103);
        stub($this->user_manager)->getUserById(102)->returns($this->user_102);

        $this->assertFalse(
            $this->notification_update_checker->hasNotificationChanged($this->repository, $new_notifications)
        );
    }
}
