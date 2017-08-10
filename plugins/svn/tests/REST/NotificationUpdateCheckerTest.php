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
use Tuleap\Svn\Repository\Repository;
use TuleapTestCase;

require_once __DIR__ . '/../bootstrap.php';

class NotificationUpdateCheckerTest extends TuleapTestCase
{
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
        $user_to_notify_dao          = mock('Tuleap\Svn\Notifications\UsersToNotifyDao');
        $ugroup_to_notify_dao        = mock('Tuleap\Svn\Notifications\UgroupsToNotifyDao');
        $mail_notification_manager   = new MailNotificationManager(
            $this->mail_notification_dao,
            $user_to_notify_dao,
            $ugroup_to_notify_dao,
            mock('\ProjectHistoryDao')
        );

        $this->notification_update_checker = new NotificationUpdateChecker($mail_notification_manager);

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
                "foo@example.com,bar@example.com",
                "/tags"
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
                "foo@example.com,bar@example.com",
                "/tags"
            ),
            new MailNotification(
                1,
                $this->repository,
                "foo@example.com",
                "/trunk"
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
                "foo@example.com,bar@example.com",
                "/tags"
            )
        );

        $old_notifications = array(
            'id'           => 1,
            'mailing_list' => "foo@example.com,bar@example.com,test@example.com",
            'svn_path'     => "/tags"
        );

        stub($this->mail_notification_dao)->searchByRepositoryId()->returnsDar($old_notifications);
        stub($this->mail_notification_dao)->searchByPathStrictlyEqual()->returnsDar($old_notifications);
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
                "bar@example.com,foo@example.com",
                "/tags"
            )
        );

        $old_notifications = array(
            'id'           => 1,
            'mailing_list' => "foo@example.com,bar@example.com",
            'svn_path'     => "/tags"
        );

        stub($this->mail_notification_dao)->searchByRepositoryId()->returnsDar($old_notifications);
        stub($this->mail_notification_dao)->searchByPathStrictlyEqual()->returnsDar($old_notifications);
        $this->assertFalse(
            $this->notification_update_checker->hasNotificationChanged($this->repository, $new_notifications)
        );
    }
}
