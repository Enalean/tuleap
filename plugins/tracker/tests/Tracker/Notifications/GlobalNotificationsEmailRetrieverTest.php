<?php
/**
 * Copyright Enalean (c) 2017. All rights reserved.
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

require_once dirname(__FILE__).'/../../bootstrap.php';

use Tracker_GlobalNotification;
use TuleapTestCase;

class GlobalNotificationsEmailRetrieverTest extends TuleapTestCase
{
    /** @var Tracker_GlobalNotification */
    private $notification;

    /** @var GlobalNotificationsEmailRetriever */
    private $retriever;

    public function setUp()
    {
        parent::setUp();

        $this->notification = aGlobalNotification()
            ->withId(1)
            ->withTrackerId(10)
            ->withAddresses('jdoe@example.com,smith@example.com')
            ->withAllUpdates(1)
            ->withCheckPermissions(0)
            ->build();

        $notified_users_dao = mock('Tuleap\Tracker\Notifications\UsersToNotifyDao');
        stub($notified_users_dao)
            ->searchUsersByNotificationId(1)
            ->returnsDar(
                array('email' => 'andrew@example.com'),
                array('email' => 'smith@example.com')
            );

        $this->retriever = new GlobalNotificationsEmailRetriever($notified_users_dao);
    }

    public function itReturnsEmailsForNotification()
    {
        $emails = $this->retriever->getNotifiedEmails($this->notification);

        $this->assertTrue(in_array('jdoe@example.com', $emails));
        $this->assertTrue(in_array('smith@example.com', $emails));
    }

    public function itReturnsEmailsOfUsersForNotification()
    {
        $emails = $this->retriever->getNotifiedEmails($this->notification);

        $this->assertTrue(in_array('andrew@example.com', $emails));
    }

    public function itRemovesDuplicates()
    {
        $emails = $this->retriever->getNotifiedEmails($this->notification);

        $this->assertEqual($emails, array_unique($emails));
    }
}
