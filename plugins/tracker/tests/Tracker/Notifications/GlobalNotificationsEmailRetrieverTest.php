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

use PFUser;
use Tracker_GlobalNotification;
use TrackerFactory;
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

        $project         = aMockProject()->build();
        $tracker         = aMockTracker()->withId(10)->build();
        $tracker_factory = mock('TrackerFactory');

        TrackerFactory::setInstance($tracker_factory);
        stub($tracker_factory)->getTrackerById(10)->returns($tracker);
        stub($tracker)->getProject()->returns($project);

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
        $notified_ugroups_dao = mock('Tuleap\Tracker\Notifications\UgroupsToNotifyDao');
        stub($notified_ugroups_dao)
            ->searchUgroupsByNotificationId(1)
            ->returnsDar(
                array('ugroup_id' => 104, 'name' => 'Developers')
            );

        $developers = aMockUGroup()
            ->withMembers(
                array(
                    aUser()->withId(201)->withStatus(PFUser::STATUS_ACTIVE)->withEmail('jdoe@example.com')->build(),
                    aUser()->withId(202)->withStatus(PFUser::STATUS_RESTRICTED)->withEmail('charles@example.com')->build(),
                    aUser()->withId(203)->withStatus(PFUser::STATUS_SUSPENDED)->withEmail('suspended@example.com')->build()
                )
            )
            ->build();

        $ugroup_manager = mock('UGroupManager');
        stub($ugroup_manager)
            ->getUGroup($project, 104)
            ->returns($developers);

        $this->retriever = new GlobalNotificationsEmailRetriever(
            $notified_users_dao,
            $notified_ugroups_dao,
            $ugroup_manager,
            $tracker_factory
        );
    }

    public function tearDown()
    {
        TrackerFactory::clearInstance();
        parent::tearDown();
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

    public function itReturnsEmailsOfUgroupMembersForNotification()
    {
        $emails = $this->retriever->getNotifiedEmails($this->notification);

        $this->assertTrue(in_array('jdoe@example.com', $emails));
        $this->assertTrue(in_array('charles@example.com', $emails));
    }

    public function itRemovesGroupMembersThatAreNotAlive()
    {
        $emails = $this->retriever->getNotifiedEmails($this->notification);

        $this->assertTrue(! in_array('suspended@example.com', $emails));
    }

    public function itRemovesDuplicates()
    {
        $emails = $this->retriever->getNotifiedEmails($this->notification);

        $this->assertEqual($emails, array_unique($emails));
    }
}
