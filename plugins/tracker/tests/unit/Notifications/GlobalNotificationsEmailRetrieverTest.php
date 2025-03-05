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

use PFUser;
use ProjectUGroup;
use Tracker_GlobalNotification;
use TrackerFactory;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class GlobalNotificationsEmailRetrieverTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private Tracker_GlobalNotification $notification;

    private GlobalNotificationsEmailRetriever $retriever;

    protected function setUp(): void
    {
        parent::setUp();

        $project         = ProjectTestBuilder::aProject()->build();
        $tracker         = TrackerTestBuilder::aTracker()->withId(10)->withProject($project)->build();
        $tracker_factory = $this->createMock(\TrackerFactory::class);

        TrackerFactory::setInstance($tracker_factory);
        $tracker_factory->method('getTrackerById')->with(10)->willReturn($tracker);

        $this->notification = $this->buildNotification();

        $notified_users_dao = $this->createMock(\Tuleap\Tracker\Notifications\UsersToNotifyDao::class);
        $notified_users_dao->method('searchUsersByNotificationId')->with(1)->willReturn(\TestHelper::arrayToDar(['email' => 'andrew@example.com'], ['email' => 'smith@example.com']));
        $notified_ugroups_dao = $this->createMock(\Tuleap\Tracker\Notifications\UgroupsToNotifyDao::class);
        $notified_ugroups_dao->method('searchUgroupsByNotificationId')->with(1)->willReturn(\TestHelper::arrayToDar(['ugroup_id' => 104, 'name' => 'Developers']));

        $developers = $this->createMock(ProjectUGroup::class);
        $developers->method('getMembers')
            ->willReturn(
                [
                    new PFUser([
                        'language_id' => 'en',
                        'user_id' => 201,
                        'status' => PFUser::STATUS_ACTIVE,
                        'email' => 'jdoe@example.com',
                    ]),
                    new PFUser([
                        'language_id' => 'en',
                        'user_id' => 202,
                        'status' => PFUser::STATUS_RESTRICTED,
                        'email' => 'charles@example.com',
                    ]),
                    new PFUser([
                        'language_id' => 'en',
                        'user_id' => 202,
                        'status' => PFUser::STATUS_SUSPENDED,
                        'email' => 'suspended@example.com',
                    ]),
                ]
            );

        $ugroup_manager = $this->createMock(\UGroupManager::class);
        $ugroup_manager->method('getUGroup')->with($project, 104)->willReturn($developers);

        $addresses_builder = new GlobalNotificationsAddressesBuilder();

        $this->retriever = new GlobalNotificationsEmailRetriever(
            $notified_users_dao,
            $notified_ugroups_dao,
            $ugroup_manager,
            $tracker_factory,
            $addresses_builder
        );
    }

    private function buildNotification(): Tracker_GlobalNotification
    {
        $notifcation = $this->createMock(Tracker_GlobalNotification::class);
        $notifcation->method('getId')->willReturn(1);
        $notifcation->method('getTrackerId')->willReturn(10);
        $notifcation->method('getAddresses')->willReturn('jdoe@example.com,smith@example.com');
        $notifcation->method('isAllUpdates')->willReturn(true);
        $notifcation->method('isCheckPermissions')->willReturn(false);

        return $notifcation;
    }

    protected function tearDown(): void
    {
        TrackerFactory::clearInstance();
        parent::tearDown();
    }

    public function testItReturnsEmailsForNotification(): void
    {
        $emails = $this->retriever->getNotifiedEmails($this->notification);

        $this->assertTrue(in_array('jdoe@example.com', $emails));
        $this->assertTrue(in_array('smith@example.com', $emails));
    }

    public function testItReturnsEmailsOfUsersForNotification(): void
    {
        $emails = $this->retriever->getNotifiedEmails($this->notification);

        $this->assertTrue(in_array('andrew@example.com', $emails));
    }

    public function testItReturnsEmailsOfUgroupMembersForNotification(): void
    {
        $emails = $this->retriever->getNotifiedEmails($this->notification);

        $this->assertTrue(in_array('jdoe@example.com', $emails));
        $this->assertTrue(in_array('charles@example.com', $emails));
    }

    public function testItRemovesGroupMembersThatAreNotAlive(): void
    {
        $emails = $this->retriever->getNotifiedEmails($this->notification);

        $this->assertTrue(! in_array('suspended@example.com', $emails));
    }

    public function testItRemovesDuplicates(): void
    {
        $emails = $this->retriever->getNotifiedEmails($this->notification);

        $this->assertEquals($emails, array_unique($emails));
    }
}
