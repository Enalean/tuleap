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

        $this->retriever = new EmailsToBeNotifiedRetriever($this->notification_manager);
    }

    public function itReturnsEmailsAsArray()
    {
        stub($this->notification_manager)->getByPath()->returns(array(
            new MailNotification($this->repository, 'jdoe@example.com, jsmith@example.com', '/path')
        ));

        $emails = $this->retriever->getEmailsToBeNotifiedForPath($this->repository, '/path');

        $expected = array('jdoe@example.com', 'jsmith@example.com');

        $this->assertEqual($emails, $expected);
    }

    public function itCombinesEmailsFromMultipleMatchingNotifications()
    {
        stub($this->notification_manager)->getByPath()->returns(array(
            new MailNotification($this->repository, 'jsmith@example.com', '/path'),
            new MailNotification($this->repository, 'jdoe@example.com', '/path')
        ));

        $emails = $this->retriever->getEmailsToBeNotifiedForPath($this->repository, '/path');

        $expected = array('jdoe@example.com', 'jsmith@example.com');

        $this->assertEqual($emails, $expected);
    }
}
