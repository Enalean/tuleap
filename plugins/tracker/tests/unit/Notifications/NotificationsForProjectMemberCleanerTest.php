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

use Project;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\Builders\UserTestBuilder;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class NotificationsForProjectMemberCleanerTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private Project $project;
    private $emails_to_notify_manager;
    private $users_to_notify_dao;
    private $unreadable_tracker;
    private $readable_tracker;
    private $factory;

    /** @var NotificationsForProjectMemberCleaner */
    private $cleaner;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();
        $this->project = ProjectTestBuilder::aProject()->withId(101)->withAccessPrivate()->build();

        $this->emails_to_notify_manager = $this->createMock(\Tracker_NotificationsManager::class);
        $this->factory                  = $this->createMock(\TrackerFactory::class);
        $this->unreadable_tracker       = $this->createMock(\Tuleap\Tracker\Tracker::class);
        $this->readable_tracker         = $this->createMock(\Tuleap\Tracker\Tracker::class);

        $this->unreadable_tracker->method('getId')->willReturn(1);
        $this->readable_tracker->method('getId')->willReturn(2);

        $this->factory->method('getTrackersByGroupId')->with(101)->willReturn([$this->unreadable_tracker, $this->readable_tracker]);

        $this->users_to_notify_dao = $this->createMock(\Tuleap\Tracker\Notifications\UsersToNotifyDao::class);

        $this->cleaner = new NotificationsForProjectMemberCleaner(
            $this->factory,
            $this->emails_to_notify_manager,
            $this->users_to_notify_dao
        );
    }

    public function testItDoesNotRemoveAnythingIfUserIsStillMemberOfTheProject(): void
    {
        $user = UserTestBuilder::aUser()
            ->withoutSiteAdministrator()
            ->withMemberOf($this->project)
            ->build();

        $this->unreadable_tracker->method('userCanView')->with($user)->willReturn(false);
        $this->readable_tracker->method('userCanView')->with($user)->willReturn(true);

        $this->emails_to_notify_manager->expects($this->never())->method('removeAddressByTrackerId');
        $this->users_to_notify_dao->expects($this->never())->method('deleteByTrackerIdAndUserId');

        $this->cleaner->cleanNotificationsAfterUserRemoval($this->project, $user);
    }

    public function testItRemovesNotificationForTrackersTheUserCannotAccess(): void
    {
        $user = UserTestBuilder::aUser()
            ->withoutSiteAdministrator()
            ->withoutMemberOfProjects()
            ->build();

        $this->unreadable_tracker->method('userCanView')->with($user)->willReturn(false);
        $this->readable_tracker->method('userCanView')->with($user)->willReturn(true);

        $this->emails_to_notify_manager
            ->expects($this->once())
            ->method('removeAddressByTrackerId')
            ->with($this->unreadable_tracker->getId(), $user);

        $this->users_to_notify_dao
            ->expects($this->once())
            ->method('deleteByTrackerIdAndUserId')
            ->with($this->unreadable_tracker->getId(), $user->getId());

        $this->cleaner->cleanNotificationsAfterUserRemoval($this->project, $user);
    }
}
