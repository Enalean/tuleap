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

use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PFUser;
use PHPUnit\Framework\TestCase;
use ProjectUGroup;
use Tracker;
use Tracker_GlobalNotification;
use TrackerFactory;

class GlobalNotificationsEmailRetrieverTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /** @var Tracker_GlobalNotification */
    private $notification;

    /** @var GlobalNotificationsEmailRetriever */
    private $retriever;

    protected function setUp(): void
    {
        parent::setUp();

        $project         = \Mockery::spy(\Project::class, ['getID' => false, 'getUnixName' => false, 'isPublic' => false]);
        $tracker         = Mockery::mock(Tracker::class)->shouldReceive('getId')->andReturn(10)->getMock();
        $tracker_factory = \Mockery::spy(\TrackerFactory::class);

        TrackerFactory::setInstance($tracker_factory);
        $tracker_factory->shouldReceive('getTrackerById')->with(10)->andReturns($tracker);
        $tracker->shouldReceive('getProject')->andReturns($project);

        $this->notification = $this->buildNotification();

        $notified_users_dao = \Mockery::spy(\Tuleap\Tracker\Notifications\UsersToNotifyDao::class);
        $notified_users_dao->shouldReceive('searchUsersByNotificationId')->with(1)->andReturns(\TestHelper::arrayToDar(array('email' => 'andrew@example.com'), array('email' => 'smith@example.com')));
        $notified_ugroups_dao = \Mockery::spy(\Tuleap\Tracker\Notifications\UgroupsToNotifyDao::class);
        $notified_ugroups_dao->shouldReceive('searchUgroupsByNotificationId')->with(1)->andReturns(\TestHelper::arrayToDar(array('ugroup_id' => 104, 'name' => 'Developers')));

        $developers = Mockery::mock(ProjectUGroup::class);
        $developers->shouldReceive('getMembers')
            ->andReturn(
                array(
                    new PFUser([
                        'language_id' => 'en',
                        'user_id' => 201,
                        'status' => PFUser::STATUS_ACTIVE,
                        'email' => 'jdoe@example.com'
                    ]),
                    new PFUser([
                        'language_id' => 'en',
                        'user_id' => 202,
                        'status' => PFUser::STATUS_RESTRICTED,
                        'email' => 'charles@example.com'
                    ]),
                    new PFUser([
                        'language_id' => 'en',
                        'user_id' => 202,
                        'status' => PFUser::STATUS_SUSPENDED,
                        'email' => 'suspended@example.com'
                    ])
                )
            );

        $ugroup_manager = \Mockery::spy(\UGroupManager::class);
        $ugroup_manager->shouldReceive('getUGroup')->with($project, 104)->andReturns($developers);

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
        $notifcation = Mockery::mock(Tracker_GlobalNotification::class);
        $notifcation->shouldReceive('getId')->andReturn(1);
        $notifcation->shouldReceive('getTrackerId')->andReturn(10);
        $notifcation->shouldReceive('getAddresses')->andReturn('jdoe@example.com,smith@example.com');
        $notifcation->shouldReceive('isAllUpdates')->andReturnTrue();
        $notifcation->shouldReceive('isCheckPermissions')->andReturnFalse();

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
