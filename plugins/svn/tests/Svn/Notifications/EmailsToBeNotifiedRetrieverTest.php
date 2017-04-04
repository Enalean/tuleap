<?php
/**
 * Copyright (c) Enalean, 2017. All Rights Reserved.
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

namespace Tuleap\Svn\Notifications;

use Tuleap\Svn\Admin\MailNotification;
use TuleapTestCase;

require_once __DIR__ .'/../../bootstrap.php';

class EmailsToBeNotifiedRetrieverTest extends TuleapTestCase
{
    private $user_dao;
    private $repository;
    private $notification_manager;

    /**
     * @var EmailsToBeNotifiedRetriever
     */
    private $retriever;

    public function setUp()
    {
        parent::setUp();

        $this->repository           = mock('Tuleap\Svn\Repository\Repository');
        $this->notification_manager = mock('Tuleap\Svn\Admin\MailNotificationManager');
        $this->user_dao             = mock('Tuleap\Svn\Notifications\UsersToNotifyDao');

        $this->retriever = new EmailsToBeNotifiedRetriever($this->notification_manager, $this->user_dao);
    }

    public function itReturnsEmailsAsArray()
    {
        stub($this->notification_manager)->getByPath()->returns(array(
            new MailNotification(1, $this->repository, 'jdoe@example.com, jsmith@example.com', '/path')
        ));
        stub($this->user_dao)->searchUsersByNotificationId()->returnsEmptyDar();

        $emails = $this->retriever->getEmailsToBeNotifiedForPath($this->repository, '/path');

        $expected = array('jdoe@example.com', 'jsmith@example.com');

        $this->assertEqual($emails, $expected);
    }

    public function itCombinesEmailsFromMultipleMatchingNotifications()
    {
        stub($this->notification_manager)->getByPath()->returns(array(
            new MailNotification(1, $this->repository, 'jsmith@example.com', '/path'),
            new MailNotification(2, $this->repository, 'jdoe@example.com', '/path')
        ));
        stub($this->user_dao)->searchUsersByNotificationId()->returnsEmptyDar();

        $emails = $this->retriever->getEmailsToBeNotifiedForPath($this->repository, '/path');

        $expected = array('jdoe@example.com', 'jsmith@example.com');

        $this->assertEqual($emails, $expected);
    }

    public function itReturnsEmailsOfUsersForNotification()
    {
        stub($this->notification_manager)->getByPath()->returns(array(
            new MailNotification(1, $this->repository, '', '/path')
        ));
        stub($this->user_dao)->searchUsersByNotificationId(1)->returnsDar(array('email' => 'jsmith@example.com'));

        $emails = $this->retriever->getEmailsToBeNotifiedForPath($this->repository, '/path');

        $expected = array('jsmith@example.com');

        $this->assertEqual($emails, $expected);
    }
}
