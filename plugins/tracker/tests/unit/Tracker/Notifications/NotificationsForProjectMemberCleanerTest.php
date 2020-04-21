<?php
/**
 * Copyright Enalean (c) 2017 - Present. All rights reserved.
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

require_once __DIR__ . '/../../bootstrap.php';

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;

class NotificationsForProjectMemberCleanerTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    private $project;
    private $user;
    private $emails_to_notify_manager;
    private $users_to_notify_dao;
    private $unreadable_tracker;
    private $readable_tracker;
    private $factory;

    /** @var NotificationsForProjectMemberCleaner */
    private $cleaner;

    protected function setUp(): void
    {
        parent::setUp();
        $this->project = \Mockery::spy(\Project::class, ['getID' => 101, 'getUnixName' => false, 'isPublic' => false]);
        $this->user    = \Mockery::spy(\PFUser::class);

        $this->user->shouldReceive('getId')->andReturns(107);
        $this->user->shouldReceive('getEmail')->andReturns('jdoe@example.com');
        $this->user->shouldReceive('getUserName')->andReturns('jdoe');

        $this->emails_to_notify_manager = \Mockery::spy(\Tracker_NotificationsManager::class);
        $this->factory                  = \Mockery::spy(\TrackerFactory::class);
        $this->unreadable_tracker       = \Mockery::spy(\Tracker::class);
        $this->readable_tracker         = \Mockery::spy(\Tracker::class);

        $this->unreadable_tracker->shouldReceive('getId')->andReturns(1);
        $this->readable_tracker->shouldReceive('getId')->andReturns(2);

        $this->unreadable_tracker->shouldReceive('userCanView')->with($this->user)->andReturns(false);
        $this->readable_tracker->shouldReceive('userCanView')->with($this->user)->andReturns(true);

        $this->factory->shouldReceive('getTrackersByGroupId')->with(101)->andReturns(array($this->unreadable_tracker, $this->readable_tracker));

        $this->users_to_notify_dao = \Mockery::spy(\Tuleap\Tracker\Notifications\UsersToNotifyDao::class);

        $this->cleaner = new NotificationsForProjectMemberCleaner(
            $this->factory,
            $this->emails_to_notify_manager,
            $this->users_to_notify_dao
        );
    }

    public function testItDoesNotRemoveAnythingIfUserIsStillMemberOfTheProject(): void
    {
        $this->user->shouldReceive('isMember')->with($this->project->getID())->andReturns(true);

        $this->emails_to_notify_manager->shouldReceive('removeAddressByTrackerId')->never();
        $this->users_to_notify_dao->shouldReceive('deleteByTrackerIdAndUserId')->never();

        $this->cleaner->cleanNotificationsAfterUserRemoval($this->project, $this->user);
    }

    public function testItRemovesNotificationForTrackersTheUserCannotAccess(): void
    {
        $this->user->shouldReceive('isMember')->with($this->project)->andReturns(false);

        $this->emails_to_notify_manager->shouldReceive('removeAddressByTrackerId')->with($this->unreadable_tracker->getId(), $this->user)
            ->once();

        $this->users_to_notify_dao->shouldReceive('deleteByTrackerIdAndUserId')->with($this->unreadable_tracker->getId(), $this->user->getId())
            ->once();

        $this->cleaner->cleanNotificationsAfterUserRemoval($this->project, $this->user);
    }
}
