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

use TuleapTestCase;

class NotificationsForProjectMemberCleanerTest extends TuleapTestCase
{
    private $project;
    private $user;
    private $emails_to_notify_manager;
    private $users_to_notify_dao;
    private $unreadable_tracker;
    private $readable_tracker;
    private $factory;

    /** @var NotificationsForProjectMemberCleaner */
    private $cleaner;

    public function setUp()
    {
        parent::setUp();
        $this->project = aMockProject()->withId(101)->build();
        $this->user    = mock('PFUser');

        stub($this->user)->getId()->returns(107);
        stub($this->user)->getEmail()->returns('jdoe@example.com');
        stub($this->user)->getUserName()->returns('jdoe');

        $this->emails_to_notify_manager = mock('Tracker_NotificationsManager');
        $this->factory                  = mock('TrackerFactory');
        $this->unreadable_tracker       = mock('Tracker');
        $this->readable_tracker         = mock('Tracker');

        stub($this->unreadable_tracker)->getId()->returns(1);
        stub($this->readable_tracker)->getId()->returns(2);

        stub($this->unreadable_tracker)->userCanView($this->user)->returns(false);
        stub($this->readable_tracker)->userCanView($this->user)->returns(true);

        stub($this->factory)
            ->getTrackersByGroupId(101)
            ->returns(array($this->unreadable_tracker, $this->readable_tracker));

        $this->users_to_notify_dao = mock('Tuleap\Tracker\Notifications\UsersToNotifyDao');

        $this->cleaner = new NotificationsForProjectMemberCleaner(
            $this->factory,
            $this->emails_to_notify_manager,
            $this->users_to_notify_dao
        );
    }

    public function itDoesNotRemoveAnythingIfUserIsStillMemberOfTheProject()
    {
        stub($this->user)->isMember($this->project->getID())->returns(true);

        expect($this->emails_to_notify_manager)->removeAddressByTrackerId()->never();
        expect($this->users_to_notify_dao)->deleteByTrackerIdAndUserId()->never();

        $this->cleaner->cleanNotificationsAfterUserRemoval($this->project, $this->user);
    }

    public function itRemovesNotificationForTrackersTheUserCannotAccess()
    {
        stub($this->user)->isMember($this->project)->returns(false);

        expect($this->emails_to_notify_manager)
            ->removeAddressByTrackerId()
            ->count(1);
        expect($this->emails_to_notify_manager)
            ->removeAddressByTrackerId($this->unreadable_tracker->getId(), $this->user)
            ->once();

        expect($this->users_to_notify_dao)
            ->deleteByTrackerIdAndUserId()
            ->count(1);
        expect($this->users_to_notify_dao)
            ->deleteByTrackerIdAndUserId($this->unreadable_tracker->getId(), $this->user->getId())
            ->once();

        $this->cleaner->cleanNotificationsAfterUserRemoval($this->project, $this->user);
    }
}
